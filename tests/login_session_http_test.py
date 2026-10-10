"""Check stale login recovery without accessing an existing user's credentials."""
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('SESENDOK_TEST_URL', 'http://localhost/sesendoknew').rstrip('/')
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

def browser():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())
def request(client, path, data=None, accept='text/html'):
    payload = urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        res = client.open(urllib.request.Request(BASE + path, data=payload, headers={'Accept': accept}), timeout=20)
    except urllib.error.HTTPError as error:
        res = error
    return res.code, res.headers, res.read().decode()

a, b = browser(), browser()
status, _, html = request(a, '/')
assert status == 200
old = re.search(r'name="_csrf" value="([^"]+)"', html).group(1)
status, headers, _ = request(b, '/login/proses', {'_csrf': old, 'username': '__login_session_test__', 'password': 'not-a-real-password'})
assert status == 303 and headers['Location'].endswith('/sesendoknew/')
status, _, html = request(b, '/')
assert status == 200 and 'Sesi form login sudah berubah' in html
assert 'auth-session.js' in html
print('PASS: stale native form rejected and returned to login with a readable message')
status, _, body = request(b, '/login/proses', {'_csrf': old}, 'application/json')
assert status == 403 and json.loads(body)['success'] is False
print('PASS: stale JSON submission still rejected with 403')
status, headers, body = request(b, '/login/session', accept='application/json')
fresh = json.loads(body)['csrf_token']
assert status == 200 and fresh != old and 'no-store' in headers['Cache-Control']
assert len(fresh) == 64 and re.fullmatch('[a-f0-9]+', fresh)
assert headers.get('Access-Control-Allow-Origin') is None
print('PASS: fresh token is available only through the same-origin, uncached session response')
status, headers, _ = request(b, '/login/proses', {'_csrf': fresh, 'username': '__login_session_test__', 'password': 'not-a-real-password'})
assert status == 302 and headers['Location'].endswith('/sesendoknew/')
status, _, body = request(b, '/')
assert status == 200 and 'Username atau password salah' in body
print('PASS: refreshed form reaches authentication; invalid credentials still rejected')
status, headers, _ = request(b, '/login/session', {'_csrf': fresh}, 'application/json')
assert status == 405 and headers['Allow'] == 'GET'
print('PASS: session token endpoint is GET-only')
