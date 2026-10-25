"""Read the delivered Excel RAB files; never fabricate missing work items.

Usage: python prepare_construction_rab_2026.py MANIFEST OUTPUT_JSON
"""
from pathlib import Path
from zipfile import ZipFile
from decimal import Decimal, ROUND_HALF_UP
import json,sys,hashlib,xml.etree.ElementTree as E,posixpath
from openpyxl import load_workbook
N={'m':'http://schemas.openxmlformats.org/spreadsheetml/2006/main','r':'http://schemas.openxmlformats.org/officeDocument/2006/relationships'}
sha=lambda p:hashlib.sha256(Path(p).read_bytes()).hexdigest()
def source_cells(path,sheet):
 with ZipFile(path) as z:
  strings=[''.join(s.itertext()) for s in E.fromstring(z.read('xl/sharedStrings.xml')).findall('m:si',N)] if 'xl/sharedStrings.xml' in z.namelist() else []
  rels={r.attrib['Id']:r.attrib['Target'] for r in E.fromstring(z.read('xl/_rels/workbook.xml.rels'))}
  w=E.fromstring(z.read('xl/workbook.xml'));sn=next(s for s in w.findall('m:sheets/m:sheet',N) if s.attrib['name']==sheet);target=rels[sn.attrib['{'+N['r']+'}id']]
  part=target.lstrip('/') if target.startswith('/') else posixpath.normpath('xl/'+target);root=E.fromstring(z.read(part));out={}
  for row in root.findall('m:sheetData/m:row',N):
   addresses=[c.attrib['r'] for c in row]
   if len(addresses)!=len(set(addresses)):raise ValueError('Duplicate Excel cells in '+str(path))
   for c in row:
    address=c.attrib['r']
    if ''.join(filter(str.isdigit,address))!=row.attrib['r']:raise ValueError('Cell/row mismatch '+address)
    t=c.attrib.get('t');v=c.find('m:v',N);value=v.text if v is not None else None
    if t=='s':value=strings[int(value)] if value is not None else None
    elif t=='inlineStr':value=''.join(c.find('m:is',N).itertext())
    elif value is not None and t not in ['str','e']:
     try:value=float(value)
     except ValueError:pass
    out[address]=value
  return out
def number(value):
 if not isinstance(value,(int,float)) or not float('-inf')<value<float('inf'):raise ValueError('Missing or invalid Excel number: '+str(value))
 return float(value)
def close(a,b):
 if abs(a-b)>max(.00001,abs(b)*1e-11):raise ValueError(f'Excel/source mismatch: {a} vs {b}')
manifest=json.loads(Path(sys.argv[1]).read_text())
contracts=[];report=[]
for entry in manifest:
 folder=Path(entry['folder']);path=folder/entry['rabFile'];source={'sheet':entry['source_sheet'],'items':entry['source_items']}
 hps=source_cells(folder/entry['hpsFile'],source['sheet']);offer=source_cells(folder/entry['offerFile'],source['sheet'])
 wb=load_workbook(path,read_only=True,data_only=True);ws=wb[entry['sheet']];rab=[]
 assert len(entry['itemRows'])==len(source['items']),'Only original work items may be imported'
 for i,(row,original) in enumerate(zip(entry['itemRows'],source['items'])):
  text=str(ws.cell(row,2).value or '').strip();unit=str(ws.cell(row,7).value or '').strip()
  assert text==str(original['description']).strip() and unit==str(original['unit']).strip()
  assert 'pembulatan' not in text.lower(),'No synthetic rounding row'
  item={'nomor':str(ws.cell(row,1).value),'uraian':text,'satuan':unit,'type':'RAB KONSTRUKSI 2026','pajak':number(ws.cell(row,11).value),'keterangan':f'Excel {entry["rup"]}; {entry["sheet"]} baris {row}; HPS {source["sheet"]}!{original["price_cell"]}'}
  for key,qcol,pcol,gcol in [('hps',4,8,19),('penawaran',5,9,20),('negoisasi',6,10,21)]:
   qty=number(ws.cell(row,qcol).value);price=number(ws.cell(row,pcol).value);gross=number(ws.cell(row,gcol).value)
   assert qty>0 and price>0 and 0<=item['pajak']<=100
   assert abs(float((Decimal(str(qty))*Decimal(str(price))*(1+Decimal(str(item['pajak']))/100)).quantize(Decimal('.01'),rounding=ROUND_HALF_UP))-gross)<.011
   close(qty,number(hps[original['qty_cell']]))
   close(price,number((hps if key=='hps' else offer)[original['price_cell']]))
   item.update({f'vol_{key}':qty,f'harga_sat_{key}':price,f'jumlah_{key}':gross})
  rab.append(item)
 totals={k:round(sum(x['jumlah_'+k] for x in rab),2) for k in ['hps','penawaran','negoisasi']}
 for col,key in [(12,'hps'),(13,'penawaran'),(14,'negoisasi')]:close(number(ws.cell(entry['totalRow'],col).value),totals[key])
 budget=number(entry['budget']);assert budget<=400000000 and totals['negoisasi']<=budget
 assert 0<totals['hps']-totals['penawaran']<=300000
 contract={'id':entry['id'],'budget':budget,'nilai_hps':entry['hps'],'nilai_kontrak':totals['negoisasi'],'rab':rab,'rab_path':str(path),'rab_sha256':sha(path),'offer_path':str(folder/entry['offerFile']),'offer_sha256':sha(folder/entry['offerFile'])}
 contracts.append(contract)
 report.append({'id':entry['id'],'rup':entry['rup'],'package':entry['package'],'hps_rekap':entry['hps'],'hps_rincian':totals['hps'],'selisih_hps':round(totals['hps']-entry['hps'],2),'penawaran_rekap':entry['offer'],'penawaran_rincian':totals['penawaran'],'selisih_penawaran':round(totals['penawaran']-entry['offer'],2),'pengurangan_rincian':round(totals['hps']-totals['penawaran'],2)})
 wb.close()
 entry.update(rab_sha256=contract['rab_sha256'],offer_sha256=contract['offer_sha256'])
 for extra in folder.glob('*.inspect.ndjson'):extra.unlink()
assert len(contracts)==19 and len({c['id'] for c in contracts})==19
Path(sys.argv[2]).write_text(json.dumps({'contracts':contracts,'report':report},ensure_ascii=False,indent=2))
Path(sys.argv[1]).write_text(json.dumps(manifest,ensure_ascii=False,indent=2))
print(json.dumps({'packages':len(contracts),'original_work_items':sum(len(c['rab']) for c in contracts),'discount_min':min(r['pengurangan_rincian'] for r in report),'discount_max':max(r['pengurangan_rincian'] for r in report),'no_rounding_items':True}))
