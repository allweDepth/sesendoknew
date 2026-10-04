"""Run against the local Apache app; remove the temporary account and uploads."""
import base64
import io
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import urllib.error
import urllib.parse
import urllib.request
import http.cookiejar
import uuid
import zipfile

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get('SESENDOK_TEST_URL', 'http://localhost/sesendoknew').rstrip('/')
BOOT = '$c=require "config/database.php";$p=new PDO("mysql:host=".$c["host"].";dbname=".$c["dbname"].";charset=utf8mb4",$c["username"],$c["password"],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);'

def php(code, *args):
    return subprocess.check_output(['php', '-r', code, *map(str, args)], cwd=ROOT)

setup = BOOT + '''$s=$p->query("SELECT id,kd_wilayah,kd_opd,tahun FROM kontrak_neo WHERE is_deleted=0 AND kd_opd<>'0' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);if(!$s)throw new RuntimeException('No contract scope for regression test');$n='AUDIT_REGRESSION_'.bin2hex(random_bytes(8));$w=bin2hex(random_bytes(24));$q=$p->prepare("INSERT INTO user_sesendok_biila (username,email,nama,password,nama_org,type_user,tgl_daftar,tahun,kd_wilayah,kd_opd,disable,disable_login) VALUES (?,?,?,?,?,'admin_opd',NOW(),?,?,?,0,0)");$q->execute([$n,$n.'@example.invalid','Regression audit',password_hash($w,PASSWORD_DEFAULT),'Regression audit',$s['tahun'],$s['kd_wilayah'],$s['kd_opd']]);echo json_encode(['id'=>(int)$p->lastInsertId(),'username'=>$n,'password'=>$w,'scope'=>$s,'master_id'=>(int)$p->query('SELECT id FROM master_dokumen_pengadaan_neo WHERE aktif=1 AND is_deleted=0 LIMIT 1')->fetchColumn()]);'''
user = json.loads(php(setup))
folders = set()
files = set()
passed = 0

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())

def request(path, data=None, uploads=None):
    headers = {}
    if uploads:
        boundary = 'sesendok-' + uuid.uuid4().hex
        chunks = []
        for name, value in (data or {}).items():
            chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode())
        for name, filename, mime, content in uploads:
            chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode() + content + b'\r\n')
        payload = b''.join(chunks) + f'--{boundary}--\r\n'.encode()
        headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
    elif data is not None:
        payload = urllib.parse.urlencode(data).encode()
    else:
        payload = None
    if data is not None and data.get('_csrf'):
        headers['X-CSRF-TOKEN'] = data['_csrf']
    try:
        response = opener.open(urllib.request.Request(BASE + path, data=payload, headers=headers), timeout=30)
    except urllib.error.HTTPError as error:
        response = error
    return response.code, response.headers, response.read()

def check(ok, message):
    global passed
    if not ok:
        raise RuntimeError('FAIL: ' + message)
    passed += 1
    print('PASS:', message)

def success(status, body, message):
    result = json.loads(body)
    check(status == 200 and result.get('success') is True, message + ('; ' + str(result.get('message')) if status != 200 or not result.get('success') else ''))
    return result.get('data')

def remember_uploads():
    code = BOOT + '''$q=$p->prepare('SELECT storage_dir FROM maps_layers WHERE user_id=?');$q->execute([(int)$argv[1]]);echo json_encode($q->fetchAll(PDO::FETCH_COLUMN));'''
    folders.update(json.loads(php(code, user['id'])))

try:
    status, _, body = request('/')
    check(status == 200 and b'formLogin' in body, 'halaman login')
    token = re.search(rb'name="_csrf" value="([^"]+)"', body).group(1).decode()
    status, headers, _ = request('/login/proses', {'username': user['username'], 'password': user['password'], '_csrf': token})
    check(status == 302 and headers.get('Location', '').endswith('/dashboard'), 'login admin OPD sementara')
    pages = ['/dashboard', '/maps', '/maps/layers', '/maps/add', '/kontrak', '/profil',
             '/pengaturan', '/renstra', '/kepegawaian', '/kepegawaian/struktur', '/kop_surat',
             '/wallchat', '/halaman_berita', '/tata_naskah', '/tata_naskah/buat', '/tata_naskah/daftar',
             '/rkpd', '/renja', '/rka', '/dpa', '/dppa', '/rkpd_perubahan', '/renja_perubahan',
             '/rka_perubahan', '/anggaran/rencana-rekening?tbl=dpa&kd_sub_keg=5.01', '/spa?tbl=organisasi&module=referensi']
    for path in pages:
        status, _, body = request(path)
        check(status == 200 and b'Metode permintaan tidak diizinkan' not in body and b'Terjadi gangguan pada aplikasi' not in body, 'GET halaman ' + path + f' ({status})')
        if path == '/maps/add':
            check(b'name="files[]"' in body and b'id="shapefileForm"' in body, 'form unggah SHP benar-benar dirender')
        if path == '/dashboard':
            token = re.search(rb'window.CSRF_TOKEN\s*=\s*"([^"]+)"', body).group(1).decode()
            check('password' not in json.loads(re.search(rb'window.app.user = (.*?);', body).group(1)), 'tidak ada hash password di halaman')
    scope = user['scope']
    for path in ['/session/status', '/maps/api/layers', '/kontrak/summary',
                 f'/kontrak/items?contract_id={scope["id"]}',
                 f'/kontrak/procurement/draft?contract_id={scope["id"]}&master_id={user["master_id"]}',
                 '/profil/periods']:
        status, _, body = request(path)
        success(status, body, 'GET data ' + path)
    status, _, body = request('/dynamic', {'action': 'list', 'tbl': 'dpa', '_csrf': token})
    success(status, body, 'tabel anggaran dimuat melalui POST dengan CSRF')
    for path in ['/maps/upload', '/maps/style', '/maps/geometry', '/maps/delete', '/logout', '/kontrak/procurement/save']:
        status, _, _ = request(path)
        check(status == 405, 'GET penyimpanan tetap ditolak: ' + path)
    status, _, _ = request('/maps/delete', {'id': '0'})
    check(status == 403, 'mutasi tanpa CSRF ditolak')

    fields = [{'name': 'ID', 'type': 'N', 'width': 10, 'decimals': 0}]
    geo = {'type': 'FeatureCollection', 'features': [{'type': 'Feature', 'properties': {'ID': 1}, 'geometry': {'type': 'Point', 'coordinates': [119.35, -1.2]}}]}
    payload = {'id': '0', 'nama_layer': 'AUDIT_REGRESSION', 'fields': json.dumps(fields), 'geojson': json.dumps(geo), '_csrf': token}
    status, _, body = request('/maps/geometry', payload)
    layer = success(status, body, 'buat folder dan simpan SHP melalui proses web')
    remember_uploads()
    layer_id = layer['id']
    status, _, body = request(f'/maps/file?id={layer_id}&part=shp')
    check(status == 200 and body[:4] == b'\x00\x00\x27\x0a', 'baca SHP privat melalui controller')
    status, _, archive = request(f'/maps/download?id={layer_id}')
    check(status == 200 and zipfile.is_zipfile(io.BytesIO(archive)), 'unduh paket SHP ZIP')
    status, _, body = request('/maps/api/layers')
    rows = success(status, body, 'daftar layer sesudah simpan')['rows']
    row = next(row for row in rows if row['id'] == layer_id)
    payload.update(id=str(layer_id), revision=row['revision'], nama_layer='AUDIT_REGRESSION_EDIT')
    status, _, body = request('/maps/geometry', payload)
    success(status, body, 'edit SHP dan buat snapshot')
    remember_uploads()
    status, _, body = request('/maps/upload', {'nama_layer': 'AUDIT_REGRESSION_ZIP', '_csrf': token}, [('files[]', 'audit.zip', 'application/zip', archive)])
    uploaded_id = success(status, body, 'unggah ZIP SHP dan seluruh sidecar')['id']
    remember_uploads()
    status, _, body = request(f'/maps/file?id={uploaded_id}&part=dbf')
    check(status == 200 and len(body) > 32, 'baca atribut DBF hasil unggahan')

    png = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jV3sAAAAASUVORK5CYII=')
    status, _, body = request('/profil/upload-photo', {'_csrf': token}, [('photo', 'audit.png', 'image/png', png)])
    success(status, body, 'buat folder dan unggah gambar profil')
    photo_code = BOOT + '''$q=$p->prepare('SELECT photo FROM user_sesendok_biila WHERE id=?');$q->execute([(int)$argv[1]]);echo json_encode($q->fetchColumn());'''
    files.add(json.loads(php(photo_code, user['id'])))
    status, headers, body = request('/profil/photo')
    check(status == 200 and headers.get('Content-Type', '').startswith('image/png') and body == png, 'gambar profil dapat dilihat kembali')
    files.add(f'public/uploads/signature/ttd_{user["id"]}.png')
    status, _, body = request('/tata_naskah/upload_signature', {'_csrf': token}, [('signature', 'audit.png', 'image/png', png)])
    success(status, body, 'unggah tanda tangan PNG')
    status, _, body = request(f'/uploads/signature/ttd_{user["id"]}.png')
    check(status == 200 and body == png, 'gambar tanda tangan dapat dilihat kembali')
    for layer_id in [layer_id, uploaded_id]:
        status, _, body = request('/maps/delete', {'id': layer_id, '_csrf': token})
        success(status, body, 'hapus layer uji melalui POST')
    for path in ['/config/database.local.php', '/storage/uploads/maps/', '/debug.txt', '/outputs/']:
        status, _, _ = request(path)
        check(status == 403, 'file internal tetap diblokir ' + path)
    status, _, _ = request('/logout', {'_csrf': token})
    check(status == 302, 'logout POST tetap bekerja')
    print(f'HTTP REGRESSION COMPLETE: {passed} checks')
finally:
    remember_uploads()
    cleanup = BOOT + '''$q=$p->prepare('DELETE FROM maps_layers WHERE user_id=?');$q->execute([(int)$argv[1]]);$q=$p->prepare("DELETE FROM user_sesendok_biila WHERE id=? AND username LIKE 'AUDIT_REGRESSION_%'");$q->execute([(int)$argv[1]]);$q=$p->prepare('DELETE FROM auth_rate_limits WHERE bucket=?');$q->execute([hash('sha256','login:account:'.strtolower($argv[2]))]);'''
    php(cleanup, user['id'], user['username'])
    for relative in folders:
        path = (ROOT / relative).resolve()
        if path.is_relative_to(ROOT / 'storage/uploads/maps') and path.is_dir():
            shutil.rmtree(path)
    for relative in files:
        path = (ROOT / relative).resolve()
        if path.is_relative_to(ROOT / 'storage/uploads') or path.is_relative_to(ROOT / 'public/uploads'):
            path.unlink(missing_ok=True)
    print('Temporary audit account and uploads removed.')
