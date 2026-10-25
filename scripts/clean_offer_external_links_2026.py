"""Detach external Excel sources while retaining cached values and native styles/VBA.

Usage: python scripts/clean_offer_external_links_2026.py MANIFEST --apply
Only delivered supplier offer files are modified; HPS sources are never changed.
"""
import sys,json,re,hashlib,posixpath,shutil
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED
from lxml import etree as E

NS='http://schemas.openxmlformats.org/spreadsheetml/2006/main';R='http://schemas.openxmlformats.org/officeDocument/2006/relationships';N={'m':NS};tag=lambda s:'{'+NS+'}'+s
EXTERNAL=re.compile(r'\[(?:\d+|[^\]]+\.xls[xmb]?)\]|file:/|(?:[A-Z]:[\\/])',re.I)
TOKENS=re.compile(r'[A-Za-z_\\][A-Za-z0-9_.\\]*')
sha=lambda p:hashlib.sha256(Path(p).read_bytes()).hexdigest()
def tokens(f):
 f=re.sub(r'"(?:[^"]|"")*"','',f)
 f=re.sub(r"'(?:[^']|'')*'!",'',f)
 return {m.group().casefold() for m in TOKENS.finditer(f) if f[m.end():].lstrip()[:1]!='('}
def values(xml):
 return {c.get('r'):(c.get('t'),E.tostring(c.find('m:v',N)) if c.find('m:v',N) is not None else None,E.tostring(c.find('m:is',N)) if c.find('m:is',N) is not None else None) for c in xml.findall('m:sheetData/m:row/m:c',N)}
def clean(path,backup_dir,apply):
 with ZipFile(path) as z:parts={n:z.read(n) for n in z.namelist()}
 oldparts=parts.copy();wb=E.fromstring(parts['xl/workbook.xml']);rels=E.fromstring(parts['xl/_rels/workbook.xml.rels']);targets={r.get('Id'):r.get('Target') for r in rels}
 sheets={}
 for idx,s in enumerate(wb.findall('m:sheets/m:sheet',N)):
  p=targets[s.get('{'+R+'}id')];sheets[p.lstrip('/') if p.startswith('/') else posixpath.normpath('xl/'+p)]=idx
 names=wb.find('m:definedNames',N);named={}
 for n in names if names is not None else []:
  key=(int(n.get('localSheetId')) if n.get('localSheetId') is not None else None,n.get('name','').casefold());named[key]=n
 tainted={k for k,n in named.items() if EXTERNAL.search(n.text or '')}
 def bad(f,scope):
  if EXTERNAL.search(f):return True
  return any(((scope,t) if (scope,t) in named else (None,t)) in tainted for t in tokens(f))
 changed=True
 while changed:
  changed=False
  for k,n in named.items():
   if k not in tainted and bad(n.text or '',k[0]):tainted.add(k);changed=True
 removed_names=0
 for k,n in named.items():
  if k in tainted or (n.get('name','').startswith('_xlnm.Print_') and re.search(r'#REF!|#N/A|#VALUE!',n.text or '')):
   names.remove(n);removed_names+=1
 for x in wb.findall('m:externalReferences',N):wb.remove(x)
 props=wb.find('m:workbookPr',N)
 if props is not None:props.set('updateLinks','never');props.set('saveExternalLinkValues','0')
 calc=wb.find('m:calcPr',N)
 if calc is not None:calc.set('fullCalcOnLoad','0');calc.set('forceFullCalc','0')
 frozen=0;missing=[];cached_errors=0;internal_kept=0
 for part,scope in sheets.items():
  xml=E.fromstring(parts[part]);before=values(xml);shared=set()
  cells=xml.findall('m:sheetData/m:row/m:c',N)
  for c in cells:
   f=c.find('m:f',N)
   if f is not None and f.get('t')=='shared' and bad(f.text or '',scope):shared.add(f.get('si'))
  modified=False
  for c in cells:
   f=c.find('m:f',N)
   if f is None:continue
   if bad(f.text or '',scope) or (f.get('t')=='shared' and f.get('si') in shared):
    if c.find('m:v',N) is None:missing.append(part+'!'+c.get('r'))
    if c.get('t')=='e':cached_errors+=1
    # Cached <v> and type are intentionally untouched, including existing errors.
    c.remove(f);frozen+=1;modified=True
   else:internal_kept+=1
  assert before==values(xml),'Cached value changed: '+part
  if modified:parts[part]=E.tostring(xml,xml_declaration=True,encoding='UTF-8',standalone=True)
 parts['xl/workbook.xml']=E.tostring(wb,xml_declaration=True,encoding='UTF-8',standalone=True)
 remove={n for n in parts if n.startswith('xl/externalLinks/') or n.startswith('xl/queryTables/') or n in ['xl/connections.xml','xl/calcChain.xml']}
 # Remove connection relationships and all links to external files. Preserve
 # embedded drawings, styles, workbook formulas and the VBA binary verbatim.
 removed_relationships=0
 for p in list(parts):
  if not p.endswith('.rels') or p in remove:continue
  xml=E.fromstring(parts[p]);removed_ids=[]
  for rel in list(xml):
   typ=rel.get('Type','');target=rel.get('Target','')
   if any(typ.endswith('/'+k) for k in ['externalLink','connections','queryTable','calcChain']) or rel.get('TargetMode')=='External':
    removed_ids.append(rel.get('Id'));xml.remove(rel);removed_relationships+=1
  if removed_ids:
   parts[p]=E.tostring(xml,xml_declaration=True,encoding='UTF-8',standalone=True)
   # Strip references to removed external hyperlink / externalData nodes.
   if '/_rels/' in p:
    parent,filename=p.split('/_rels/',1);owner=parent+'/'+filename.removesuffix('.rels')
    if owner in parts and owner not in remove:
     x=E.fromstring(parts[owner]);altered=False
     for node in list(x.iter()):
      rid=node.get('{'+R+'}id');link=node.get('{'+R+'}link')
      if rid in removed_ids and node.getparent() is not None:node.getparent().remove(node);altered=True
      if link in removed_ids:node.attrib.pop('{'+R+'}link');altered=True
     if altered:parts[owner]=E.tostring(x,xml_declaration=True,encoding='UTF-8',standalone=True)
 ct=E.fromstring(parts['[Content_Types].xml'])
 for node in list(ct):
  if node.get('PartName','').lstrip('/') in remove:ct.remove(node)
 parts['[Content_Types].xml']=E.tostring(ct,xml_declaration=True,encoding='UTF-8',standalone=True)
 for p in remove:parts.pop(p,None)
 # Rebuild imported charts' external series from their own stored cache.
 for p in list(parts):
  if not p.startswith('xl/charts/') or not p.endswith('.xml'):continue
  x=E.fromstring(parts[p]);altered=False
  for n in list(x.iter()):
   if E.QName(n).localname not in ['numRef','strRef']:continue
   f=next((c for c in n if E.QName(c).localname=='f'),None)
   if f is None or not EXTERNAL.search(f.text or ''):continue
   cache=next((c for c in n if E.QName(c).localname in ['numCache','strCache']),None)
   if cache is not None:
    ns=E.QName(n).namespace;n.tag='{'+ns+'}'+('numLit' if E.QName(n).localname=='numRef' else 'strLit')
    for c in list(n):n.remove(c)
    for c in list(cache):n.append(c)
    altered=True
  if altered:parts[p]=E.tostring(x,xml_declaration=True,encoding='UTF-8',standalone=True)
 assert parts['xl/styles.xml']==oldparts['xl/styles.xml'],'Style changed'
 for p in oldparts:
  if 'vbaProject' in p:assert parts[p]==oldparts[p],'VBA changed'
 for p in sheets:assert values(E.fromstring(parts[p]))==values(E.fromstring(oldparts[p])),'Cell values changed'
 for p,b in parts.items():
  if p.endswith('.rels'):assert not any(r.get('TargetMode')=='External' for r in E.fromstring(b))
 assert not any('externalLinks/' in p for p in parts)
 report={'file':str(path),'external_parts_removed':len(remove),'external_names_removed':removed_names,'external_formulas_frozen':frozen,'internal_formulas_retained':internal_kept,'cached_errors_preexisting':cached_errors,'missing_cached_values':missing,'styles_preserved':True,'vba_preserved':True,'cached_values_unchanged':True,'external_relationships_removed':removed_relationships}
 if apply:
  backup_dir.mkdir(parents=True,exist_ok=True);backup=backup_dir/path.name
  if not backup.exists():shutil.copyfile(path,backup)
  temp=path.with_suffix(path.suffix+'.next')
  with ZipFile(temp,'w',ZIP_DEFLATED) as z:
   for p,b in parts.items():z.writestr(p,b)
  with ZipFile(temp) as z:assert z.testzip() is None
  temp.replace(path);report['sha256']=sha(path)
 return report
if __name__=='__main__':
 manifest=Path(sys.argv[1]);entries=json.loads(manifest.read_text());apply='--apply' in sys.argv;reports=[]
 for e in entries:
  r=clean(Path(e['folder'])/e['offerFile'],Path('/private/tmp/konstruksi2026/offer-before-unlink'),apply);reports.append(r)
  if apply:e['offer_sha256']=r['sha256']
  print(json.dumps({k:r[k] for k in ['external_parts_removed','external_formulas_frozen','internal_formulas_retained','cached_errors_preexisting']}|{'id':e['id']}),flush=True)
 if apply:
  manifest.write_text(json.dumps(entries,ensure_ascii=False,indent=2)+'\n');(manifest.parent/'verifikasi-tautan-penawaran.json').write_text(json.dumps(reports,ensure_ascii=False,indent=2)+'\n')
