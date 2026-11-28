"""Check actual TCPDF geometry and text, not HTML implementation details."""
from pathlib import Path
from pypdf import PdfReader
base=Path(__file__).resolve().parents[1]/'outputs/swakelola-tcpdf-20261128/regression'
def inspect(name):
 r=PdfReader(base/name);pieces=[]
 for i,p in enumerate(r.pages):
  def visitor(text,cm,tm,font,size):
   if text.strip():pieces.append((i,text.strip(),tm[4],tm[5]))
  p.extract_text(visitor_text=visitor)
 return r,pieces
r,a=inspect('manual-before.pdf');r2,b=inspect('manual-after.pdf');small,c=inspect('paper-a5.pdf')
def find(items,token):return next(x for x in items if token in x[1])
assert 'TCPDF'in str(r.metadata.producer)
assert abs(float(r.pages[0].mediabox.height)*25.4/72-330)<1
assert abs(float(small.pages[0].mediabox.width)*25.4/72-148)<1
assert find(b,'KOLOM_B')[2]-find(a,'KOLOM_B')[2]>60,'Manual resize did not move printed column'
assert find(b,'KOLOM_C')[2]<find(a,'KOLOM_C')[2],'Remaining columns did not follow manual grid'
assert find(a,'NAMA_PPK')[0]==find(a,'Pejabat Pembuat Komitmen')[0]==find(a,'NAMA_PELAKSANA')[0]
assert find(a,'•')[2]<find(a,'AWAL_BULLET')[2],'Bullet is merged into the body'
assert find(a,'1.1')[2]<find(a,'AWAL_BUTIR')[2],'Number is merged into the body'
assert find(a,'1.2')[3]<find(a,'AWAL_BUTIR')[3]-20,'Distinct paragraphs were compacted together'
assert find(b,'PARAGRAF_TAMBAHAN_BEBAS'),'New unmapped paragraph was rejected'
print('PASS: native TCPDF, OPD F4/A5, manual printed widths, separate numbering/paragraphs, editable added content, signature grouping.')

flow,items=inspect('flowing-frame.pdf')
import re
markers=[]
for page,text,x,y in items:
 for token in re.findall(r'FLOW_\d{3}',text):markers.append((page,token,y))
assert len(markers)==80 and len({m[1]for m in markers})==80,'Frame lost or duplicated paragraphs'
for prev,cur in zip(markers,markers[1:]):
 if prev[0]==cur[0]:assert abs(prev[2]-cur[2])>12,'Frame paragraphs overlap after a page break'
assert find(items,'NAMA_PPK')[0]==find(items,'NAMA_PELAKSANA')[0]
print('PASS: long Word frame flows across OPD pages without overlapping, missing or duplicating paragraphs.')

nested,ni=inspect('nested-frame.pdf')
after=find(ni,'SETELAH_TABEL');last=find(ni,'ROW_060')
assert after[0]>=last[0]
if after[0]==last[0]:assert after[3]<last[3]-8,'Nested frame overlaps the next paragraph'
assert find(ni,'NAMA_PPK')[0]==find(ni,'NAMA_PELAKSANA')[0]
print('PASS: short-text outer frame with long nested table flows without overlap.')
r,k=inspect('kak-columns.pdf')
markers=[x for x in k if x[1].startswith('SEC_FLOW_')]
assert len(markers)==45, len(markers)
xs=[x[2] for x in markers]
assert max(xs)-min(xs)<1, (min(xs),max(xs))
assert len(set(x[0] for x in markers))>1
assert min(xs)>150, min(xs)
print('PASS: KAK body stays in its manually sized right column across OPD page breaks; all 45 paragraphs appear once.')
r,c=inspect('closing-signature.pdf')
assert find(c,'CLOSING_GROUP')[0]==find(c,'NAMA_PPK')[0]==find(c,'NAMA_PELAKSANA')[0]
print('PASS: closing paragraph stays with both signatures despite twelve empty Word spacer paragraphs.')
