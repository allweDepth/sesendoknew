"""Remove unrelated template residue while protecting the RAB dependency graph."""
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED
from lxml import etree as E
from openpyxl.formula.translate import Translator
import json,re,sys,hashlib,shutil,posixpath
M='http://schemas.openxmlformats.org/spreadsheetml/2006/main';R='http://schemas.openxmlformats.org/officeDocument/2006/relationships';N={'m':M};tag=lambda x:'{'+M+'}'+x
def pos(a):
 n=0
 for ch in re.sub(r'\d','',a):n=n*26+ord(ch)-64
 return n,int(re.sub(r'\D','',a))
def text(c,strings):
 v=c.find('m:v',N)
 if c.get('t')=='s':return strings[int(v.text)] if v is not None and v.text else ''
 if c.get('t')=='inlineStr':return ''.join(c.find('m:is',N).itertext())
 return v.text if v is not None else None
def val(c):return(c.get('t'),c.find('m:v',N).text if c.find('m:v',N) is not None else None,''.join(c.find('m:is',N).itertext()) if c.find('m:is',N) is not None else None)
META=re.compile(r'^(?:CV\.?\s|PT\.?\s|UD\.?\s|Nip\.?\s*[:.]?\s*\d|NIP\s*\d|Ir\.?\s*ANDI\s+SYARIFUDDIN|PRIYATTISTO|NIRWAN\s*,?\s*ST|SUPARDI\s*[, .]|MUH\.?\s*RIZKY|Kepala Dinas Perkebunan|Team Leader|Core Team|Konsultan Perencana|Diketahui\s*Oleh|Disetujui\s*Oleh|Dibuat\s*Oleh|Disusun\s*Oleh|Diperiksa\s*:)',re.I)
def clean(e,plan):
 path=Path(e['folder'])/e['offerFile'];backup=Path('/private/tmp/konstruksi2026/before-content-cleanup')/path.name;backup.parent.mkdir(exist_ok=True)
 if not backup.exists():shutil.copyfile(path,backup)
 protected=set(plan['protected_cells']);core=plan['recap_sheet'];kind=plan['kind'];endcol=20 if kind=='rabat' else 9 if kind=='pematangan' else 10;endrow=41 if kind in ['rabat','pematangan'] else 46
 with ZipFile(path) as z:parts={p:z.read(p) for p in z.namelist()}
 oldparts=parts.copy();strings=[''.join(s.itertext()) for s in E.fromstring(parts['xl/sharedStrings.xml']).findall('m:si',N)] if 'xl/sharedStrings.xml' in parts else []
 wb=E.fromstring(parts['xl/workbook.xml']);rels={r.get('Id'):r.get('Target') for r in E.fromstring(parts['xl/_rels/workbook.xml.rels'])};count=0;errors=0;annotations=0
 for sheet in wb.findall('m:sheets/m:sheet',N):
  sn=sheet.get('name');p=rels[sheet.get('{'+R+'}id')];p=p.lstrip('/') if p.startswith('/') else posixpath.normpath('xl/'+p);s=E.fromstring(parts[p]);cells=s.findall('m:sheetData/m:row/m:c',N);deletes=set();saved={c.get('r'):val(c) for c in cells if sn+'!'+c.get('r') in protected}
  for c in cells:
   a=c.get('r');key=sn+'!'+a;v=text(c,strings);x,y=pos(a)
   if key in protected:continue
   outside=sn==core and (not(2<=x<=endcol and 3<=y<=endrow) or (kind=='rabat' and 8<=x<=17 and 11<=y<=29))
   iserror=c.get('t')=='e' or (isinstance(v,str) and v.strip() in ['#REF!','#N/A','#VALUE!','#NAME?','#DIV/0!'])
   annotation=isinstance(v,str) and (META.search(v.strip()) or re.match(r'^(?:Mamuju|Pasangkayu)\s*,?.*\b(?:201\d|202[0-5])\b',v.strip(),re.I))
   # Correct supplier identity and signature are part of the cleaned recap.
   if sn==core and not outside:annotation=False
   if v==e['company'] or (sn==e['source_sheet'] and y==3):annotation=False
   if outside or iserror or annotation:
    deletes.add(a);count+=1;errors+=bool(iserror);annotations+=bool(annotation)
  # Retained shared-formula followers must not lose their master.
  shared={f.get('si'):(c.get('r'),f.text) for c in cells if (f:=c.find('m:f',N)) is not None and f.get('t')=='shared' and f.text}
  for c in cells:
   f=c.find('m:f',N)
   if f is None or f.get('t')!='shared' or c.get('r') in deletes:continue
   master=shared.get(f.get('si'))
   if master and master[0] in deletes:
    f.text=Translator('='+master[1],origin=master[0]).translate_formula(c.get('r')).removeprefix('=')
    for key in ['si','ref','t']:f.attrib.pop(key,None)
  for c in cells:
   if c.get('r') in deletes:c.getparent().remove(c)
  if sn==core:
   sd=s.find('m:sheetData',N)
   for row in list(sd):
    if not 3<=int(row.get('r'))<=endrow:sd.remove(row)
   cols=s.find('m:cols',N)
   if cols is not None:
    for col in list(cols):
     if int(col.get('min'))>endcol:cols.remove(col)
     elif int(col.get('max'))>endcol:col.set('max',str(endcol))
   merges=s.find('m:mergeCells',N)
   if merges is not None:
    for m in list(merges):
     first,last=(m.get('ref').split(':')+[m.get('ref')])[:2];x,y=pos(first);xx,yy=pos(last)
     if not(2<=x<=xx<=endcol and 3<=y<=yy<=endrow) or (kind=='rabat' and x>=8 and xx<=17 and y>=11 and yy<=29):merges.remove(m)
    merges.set('count',str(len(merges)))
   end='T' if kind=='rabat' else 'I' if kind=='pematangan' else 'J';s.find('m:dimension',N).set('ref',f'B3:{end}{endrow}')
   for view in s.findall('m:sheetViews/m:sheetView',N):
    view.set('view','normal');view.set('topLeftCell','A1')
    for selected in view.findall('m:selection',N):selected.set('activeCell','B3');selected.set('sqref','B3')
   for group in ['rowBreaks','colBreaks']:
    for node in s.findall('m:'+group,N):s.remove(node)
   setup=s.find('m:pageSetup',N)
   if setup is not None:setup.attrib.pop('scale',None);setup.set('fitToWidth','1');setup.set('fitToHeight','1')
   props=s.find('m:sheetPr',N)
   if props is not None:
    page=props.find('m:pageSetUpPr',N)
    if page is None:page=E.SubElement(props,tag('pageSetUpPr'))
    page.set('fitToPage','1')
  remaining={c.get('r'):val(c) for c in s.findall('m:sheetData/m:row/m:c',N) if c.get('r') in saved};assert saved==remaining,(e['id'],sn,'RAB dependency value changed')
  parts[p]=E.tostring(s,xml_declaration=True,encoding='UTF-8',standalone=True)
 assert parts['xl/styles.xml']==oldparts['xl/styles.xml']
 for p in parts:
  if 'vbaProject' in p:assert parts[p]==oldparts[p]
 tmp=path.with_suffix(path.suffix+'.next')
 with ZipFile(tmp,'w',ZIP_DEFLATED) as z:
  for p,data in parts.items():z.writestr(p,data)
 with ZipFile(tmp) as z:assert z.testzip() is None
 tmp.replace(path);return {'id':e['id'],'cleared_cells':count,'unrelated_error_cells_cleared':errors,'legacy_annotations_cleared':annotations,'financial_dependency_cells_preserved':len(protected),'styles_and_vba_preserved':True}
if __name__=='__main__':
 mp=Path(sys.argv[1]);entries=json.loads(mp.read_text());plans={x['id']:x for x in json.load(open(sys.argv[2]))};report=[]
 for e in entries:
  r=clean(e,plans[e['id']]);report.append(r);e['offer_sha256']=hashlib.sha256((Path(e['folder'])/e['offerFile']).read_bytes()).hexdigest();print(json.dumps(r),flush=True)
 mp.write_text(json.dumps(entries,ensure_ascii=False,indent=2)+'\n');(mp.parent/'verifikasi-pembersihan-lembar.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
