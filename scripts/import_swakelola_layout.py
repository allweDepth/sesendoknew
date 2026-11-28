"""Read Word typography and table geometry as inert metadata for editable TCPDF blocks."""
from pathlib import Path
from zipfile import ZipFile
from lxml import etree as E
from copy import deepcopy
import json,sys,hashlib,re,importlib.util,gzip
sys.dont_write_bytecode=True
NS={'w':'http://schemas.openxmlformats.org/wordprocessingml/2006/main'};Q=lambda s:'{'+NS['w']+'}'+s
spec=importlib.util.spec_from_file_location('mds',Path(__file__).with_name('import_swakelola_sources.py'));imp=importlib.util.module_from_spec(spec);spec.loader.exec_module(imp)
def props(el):
 out={}
 if el is None:return out
 for ch in el:
  name=E.QName(ch).localname
  for a,v in ch.attrib.items():out[name+'.'+E.QName(a).localname]=v
  if not ch.attrib:out[name+'.val']='1'
 return out
def boolprop(d,k):return str(d.get(k+'.val','0')).lower()not in['0','false','off','none']
def number(d,k,default=0):
 try:return float(d.get(k,default))
 except(ValueError,TypeError):return default
class Package:
 def __init__(self,path):
  self.path=path
  with ZipFile(path)as z:
   self.root=E.fromstring(z.read('word/document.xml'));self.prefix=imp.numbering_prefixes(z,self.root);styles=E.fromstring(z.read('word/styles.xml'))
  self.num_levels={}
  with ZipFile(path)as z:
   if 'word/numbering.xml'in z.namelist():
    nr=E.fromstring(z.read('word/numbering.xml'));abstract={a.get(Q('abstractNumId')):a for a in nr.findall('w:abstractNum',NS)}
    for num in nr.findall('w:num',NS):
     ref=num.find('w:abstractNumId',NS);a=abstract.get(ref.get(Q('val')))if ref is not None else None
     if a is not None:self.num_levels[num.get(Q('numId'))]={l.get(Q('ilvl')):props(l.find('w:pPr',NS))for l in a.findall('w:lvl',NS)}
  self.styles={s.get(Q('styleId')):s for s in styles.findall('w:style',NS)};self.default_p=props(styles.find('w:docDefaults/w:pPrDefault/w:pPr',NS));self.default_r=props(styles.find('w:docDefaults/w:rPrDefault/w:rPr',NS));self.cache={}
 def style(self,sid):
  if sid in self.cache:return self.cache[sid]
  s=self.styles.get(sid)
  if s is None:return {},{}
  based=s.find('w:basedOn',NS);p,r=self.style(based.get(Q('val')))if based is not None else ({},{})
  p=p|props(s.find('w:pPr',NS));r=r|props(s.find('w:rPr',NS));self.cache[sid]=(p,r);return p,r
 def runfmt(self,r):
  font=r.get('rFonts.ascii',r.get('rFonts.hAnsi','Arial'));font=font if font not in['+minorHAnsi','+majorHAnsi']else'Arial'
  return {'font':font,'size':number(r,'sz.val',22)/2,'bold':boolprop(r,'b'),'italic':boolprop(r,'i'),'underline':r.get('u.val','none')not in['none','0'],'color':r.get('color.val','000000')if r.get('color.val')!='auto'else'000000'}
 def paragraph(self,p):
  b=imp.paragraph(p);sid=p.xpath('./w:pPr/w:pStyle/@w:val',namespaces=NS);sp,sr=self.style(sid[0]if sid else'Normal');nid=p.xpath('./w:pPr/w:numPr/w:numId/@w:val',namespaces=NS);level=p.xpath('./w:pPr/w:numPr/w:ilvl/@w:val',namespaces=NS);np=self.num_levels.get(nid[0],{}).get(level[0]if level else'0',{})if nid else{};pp=self.default_p|sp|np|props(p.find('w:pPr',NS));rr=self.default_r|sr;f=self.runfmt(rr|props(p.find('w:pPr/w:rPr',NS)))
  f.update(before=number(pp,'spacing.before')/20,after=number(pp,'spacing.after')/20,left=number(pp,'ind.left')/20,right=number(pp,'ind.right')/20,first=(number(pp,'ind.firstLine')-number(pp,'ind.hanging'))/20,keep_next=boolprop(pp,'keepNext'),keep_lines=boolprop(pp,'keepLines'),page_break_before=boolprop(pp,'pageBreakBefore'))
  line=number(pp,'spacing.line',240);rule=pp.get('spacing.lineRule','auto');f['line_height']=line/240 if rule=='auto'else max(1,line/(20*f['size']));f['align']=pp.get('jc.val','left');b['align']=f['align']if f['align']in['left','right','center','justify']else'left'
  # Only direct runs are text. Nested drawing text remains in the imported model block.
  runs=[]
  for r in p.findall('w:r',NS):
   fmt=self.runfmt(rr|props(r.find('w:rPr',NS)));value=''
   for n in r:
    if n.tag==Q('t'):value+=n.text or''
    elif n.tag==Q('tab'):value+='\t'
    elif n.tag==Q('br')and n.get(Q('type'))!='page':value+='\n'
   if value:runs.append({'text':value,**fmt})
  if runs:
   base=self.runfmt(rr);f.update({k:base[k]for k in ['bold','italic','underline']});weights={}
   for run in runs:
    key=(run['font'],run['size']);weights[key]=weights.get(key,0)+len(run['text'])
   f['font'],f['size']=max(weights,key=weights.get)
   total=sum(len(run['text'])for run in runs)or 1
   for key in ['bold','italic','underline']:f[key]=sum(len(run['text'])for run in runs if run[key])/total>0.5
  prefix=self.prefix.get(p,'');b['text']=prefix+b['text'];f['prefix']=prefix;f['runs']=runs;f['runs_text']=prefix+''.join(x['text']for x in runs);b['word_format']=f;b['source_path']=self.pathof(p);return b
 def pathof(self,el):
  out=[]
  while el.getparent()is not None:
   par=el.getparent();out.append(par.index(el));el=par
  return '.'.join(map(str,reversed(out)))
 def block(self,el):
  if el.tag==Q('p'):return self.paragraph(el)
  if el.tag!=Q('tbl'):raise ValueError(el.tag)
  grid=[float(v)for v in el.xpath('./w:tblGrid/w:gridCol/@w:w',namespaces=NS)];tp=props(el.find('w:tblPr',NS));borderel=el.find('w:tblPr/w:tblBorders',NS);borders={E.QName(e).localname:props_edge(e)for e in borderel}if borderel is not None else{}
  rows=[];rowfmt=[]
  for row in el.findall('w:tr',NS):
   cells=[];rf=props(row.find('w:trPr',NS));rowfmt.append({'header':boolprop(rf,'tblHeader'),'keep_together':boolprop(rf,'cantSplit')})
   for tc in row.findall('w:tc',NS):
    cp=props(tc.find('w:tcPr',NS));cb=tc.find('w:tcPr/w:tcBorders',NS);cellborders={E.QName(e).localname:props_edge(e)for e in cb}if cb is not None else{}
    cells.append({'colspan':int(number(cp,'gridSpan.val',1)),'word_cell':{'borders':cellborders,'valign':cp.get('vAlign.val','top'),'fill':cp.get('shd.fill',''),'vmerge':cp.get('vMerge.val','continue')if tc.find('w:tcPr/w:vMerge',NS)is not None else None},'blocks':[self.block(x)for x in tc if x.tag in[Q('p'),Q('tbl')]]})
   rows.append(cells)
  return {'type':'table','rows':rows,'source_path':self.pathof(el),'column_widths':[v/sum(grid)*100 for v in grid]if grid and sum(grid)else[], 'word_table':{'width_mm':sum(grid)/1440*25.4 if grid else 0,'indent_mm':number(tp,'tblInd.w')/1440*25.4,'align':tp.get('jc.val','left'),'borders':borders,'rows':rowfmt,'cell_padding_mm':1.2},'borderless':not any(x['style']not in['nil','none']for x in borders.values())}
 def page(self,start=0):
  body=self.root.find('w:body',NS);els=[e for e in body if e.tag in[Q('p'),Q('tbl')]];sec=None
  for e in els[start:]:
   ss=e.find('.//w:sectPr',NS)
   if ss is not None:sec=ss;break
  if sec is None:sec=body.find('w:sectPr',NS)
  p=props(sec);tw=lambda k,d:number(p,k,d)/1440*25.4
  return {'width':tw('pgSz.w',11906),'height':tw('pgSz.h',16838),'left':tw('pgMar.left',1440),'right':tw('pgMar.right',1440),'top':tw('pgMar.top',1440),'bottom':tw('pgMar.bottom',1440)}
 def blocks(self,start=0,end=None):
  els=[e for e in self.root.find('w:body',NS)if e.tag in[Q('p'),Q('tbl')]];return [self.block(e)for e in els[start:end]]
def props_edge(e):return {'style':e.get(Q('val'),'none'),'size':float(e.get(Q('sz'),'4'))/8,'color':e.get(Q('color'),'000000')if e.get(Q('color'))!='auto'else'000000'}
def main():
 root=Path(__file__).resolve().parents[1];base=Path(sys.argv[1]);cat=json.loads((root/'resources/procurement/swakelola/catalog.json').read_text());packages={};out={}
 for t in cat['templates']:
  path=base/('MDS Tipe '+t['type'])/t['filename'];p=packages.setdefault(str(path),None)
  if p is None:p=packages[str(path)]=Package(path)
  out[t['code']]={'page':p.page(t['start']),'blocks':p.blocks(t['start'],t['end']),'source_sha256':hashlib.sha256(path.read_bytes()).hexdigest()}
 target=root/'resources/procurement/swakelola/layout';target.mkdir(exist_ok=True)
 for code,data in out.items():(target/(code+'.json.gz')).write_bytes(gzip.compress(json.dumps(data,ensure_ascii=False,separators=(',',':')).encode(),mtime=0))
 custom=0
 for f in(root/'storage/swakelola-word').glob('*/*/document.docx'):
  p=Package(f);(f.parent/'layout.json').write_text(json.dumps({'page':p.page(),'blocks':p.blocks()},ensure_ascii=False,separators=(',',':')));custom+=1
 print(len(out),'model; ',custom,'dokumen tersimpan; metadata TCPDF',sum(f.stat().st_size for f in target.glob('*.gz')),'bytes')
if __name__=='__main__':main()
