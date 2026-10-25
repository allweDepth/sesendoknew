"""Normalize native OOXML styles without changing their effective appearance."""
from zipfile import ZipFile,ZIP_DEFLATED
from pathlib import Path
from lxml import etree as E
import json,sys,re
NS='http://schemas.openxmlformats.org/spreadsheetml/2006/main';N={'m':NS}
def signature(node):return (node.tag,tuple(sorted(node.attrib.items())),node.text or '',tuple(signature(c) for c in node))
def normalize(path):
 with ZipFile(path) as z:parts={n:z.read(n) for n in z.namelist()}
 s=E.fromstring(parts['xl/styles.xml']);report={}
 for group,attr in [('fonts','fontId'),('fills','fillId'),('borders','borderId')]:
  el=s.find('m:'+group,N);lookup={};mapping={};unique=[]
  for i,node in enumerate(el):
   key=signature(node)
   if key not in lookup:lookup[key]=len(unique);unique.append(node)
   mapping[i]=lookup[key]
  report[group]=[len(el),len(unique)]
  for node in list(el):el.remove(node)
  for node in unique:el.append(node)
  el.set('count',str(len(el)))
  for node in s.iter():
   if attr in node.attrib:
    original=int(node.get(attr));assert original in mapping,(path,attr,original)
    node.set(attr,str(mapping[original]))
 rank={'alignment':0,'protection':1,'extLst':2};ordered=0
 for el in s.findall('m:cellXfs/m:xf',N)+s.findall('m:cellStyleXfs/m:xf',N):
  before=list(el);after=sorted(before,key=lambda x:rank.get(E.QName(x).localname,99))
  if before!=after:
   for x in before:el.remove(x)
   for x in after:el.append(x)
   ordered+=1
 report['xf_child_order_fixed']=ordered
 parts['xl/styles.xml']=E.tostring(s,xml_declaration=True,encoding='UTF-8',standalone=True)
 # CT_Hyperlinks requires at least one hyperlink. Removing external links may
 # leave this container empty, which Excel reports as a repaired worksheet.
 empty_links=0
 for name in parts:
  if name.startswith('xl/worksheets/') and name.endswith('.xml'):
   parts[name],n=re.subn(rb'<(?:[A-Za-z0-9_]+:)?hyperlinks\s*/>',b'',parts[name]);empty_links+=n
 report['empty_hyperlink_containers_removed']=empty_links
 temp=path.with_suffix(path.suffix+'.next')
 with ZipFile(temp,'w',ZIP_DEFLATED) as z:
  for p,data in parts.items():z.writestr(p,data)
 with ZipFile(temp) as z:assert z.testzip() is None
 temp.replace(path);return report
if __name__=='__main__':
 m=json.load(open(sys.argv[1]));selected=int(sys.argv[2]) if len(sys.argv)>2 else None
 for e in m:
  if selected is not None and e['id']!=selected:continue
  for k in ['offerFile','rabFile']:print(json.dumps({'id':e['id'],'file':k,**normalize(Path(e['folder'])/e[k])}),flush=True)
