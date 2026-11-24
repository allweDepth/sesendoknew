"""Rebuild the SSUK from the supplied PDF's printed labels, not Word list counters."""
import json,re,hashlib,sys,logging
from pypdf import PdfReader
from pathlib import Path
logging.getLogger('pypdf').setLevel(logging.ERROR)
source=Path(sys.argv[1])
metadata=json.loads(Path('resources/procurement/reference/mini-kompetisi-source.json').read_text())['ssuk_verified_pdf']
assert hashlib.sha256(source.read_bytes()).hexdigest()==metadata['sha256'], 'PDF source differs: review page bounds before importing'
reader=PdfReader(source)
src=''.join('\fPAGE '+str(i+1)+'\n'+reader.pages[i].extract_text(extraction_mode='layout') for i in range(96,134))
rows=[]; current_heading=None; current_body=None

def add(text,fmt):
 r=dict(type='paragraph',align='left' if fmt=='clause-heading' else 'justify',format=fmt,style=['bold'] if fmt=='clause-heading' else [],text=re.sub(r'\s+',' ',text).strip());rows.append(r);return r
for pg in src.split('\fPAGE '):
 m=re.match(r'(\d+)\n',pg)
 if not m or not 97<=int(m[1])<=134:continue
 for line in pg.splitlines()[1:]:
  if not line.strip() or 'Dokumen ini tidak terkendali' in line or re.match(r'^\s*- \d+ -\s*$',line):continue
  if 'SYARAT-SYARAT UMUM' in line or 'II.   LAMPIRAN KONTRAK' in line:continue
  left,right=line[:37].strip(),line[37:].strip()
  if re.match(r'^[A-H]\.(?:\d)?\s',line.strip()):
   add(line.strip(),'clause-heading'); current_heading=None;current_body=None;continue
  if re.match(r'^\d{1,2}\.\s',left):
   current_heading=add(left,'clause-heading'); current_body=None
  elif left:
   assert current_heading is not None,(m[1],line)
   current_heading['text']+=' '+left
  if not right:continue
  if current_body is None or re.match(r'^(?:\d{1,2}\.\d+\.?|[a-z]\.|\d+\))\s',right):
   current_body=add(right,'hanging-list' if re.match(r'^(?:\d{1,2}\.\d+\.?|[a-z]\.|\d+\))\s',right) else 'body')
  else: current_body['text']+=' '+re.sub(r'\s+',' ',right)
heads=[int(re.match(r'^(\d+)\.',r['text'])[1]) for r in rows if r['format']=='clause-heading' and re.match(r'^\d+\.',r['text'])]
assert heads==list(range(1,84)),heads
# Every printed subclause belongs to its printed main clause.
current=0
for r in rows:
 if r['format']=='clause-heading' and (m:=re.match(r'^(\d+)\.',r['text'])):current=int(m[1])
 if r['format']!='clause-heading' and (m:=re.match(r'^(\d+)\.\d+',r['text'])):assert int(m[1])==current,(current,r['text'])
Path('resources/procurement/reference/ssuk-mini-kompetisi-bina-marga.json').write_text(json.dumps(rows,ensure_ascii=False,indent=2)+'\n')
assert hashlib.sha256(Path('resources/procurement/reference/ssuk-mini-kompetisi-bina-marga.json').read_bytes()).hexdigest()==metadata['content_sha256']
print('PASS: 83 clauses rebuilt from printed PDF pages 97–134')
