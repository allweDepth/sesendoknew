import json,re,gzip,hashlib
from pathlib import Path
base=Path(__file__).resolve().parent
path=base.parent.parent/'config/sesendoknew_db.sql'
exports=json.loads((base/'process-after.json').read_text())
raw=path.read_bytes(); text=raw.decode()
backup=base/'sesendoknew_db-before-process.sql.gz'
if not backup.exists():
 with gzip.open(backup,'wb') as f:f.write(raw)
def quote(v):
 if v is None:return 'NULL'
 if isinstance(v,(int,float)):return str(v)
 return "'"+v.replace('\\','\\\\').replace('\0','\\0').replace('\n','\\n').replace('\r','\\r').replace("'","\\'").replace('\x1a','\\Z')+"'"
def parse(s):
 out=[];i=1
 while i<len(s):
  if s[i]=="'":
   i+=1;v=''
   while True:
    if s[i]=='\\':
     i+=1;v+={'0':'\0','n':'\n','r':'\r','Z':'\x1a'}.get(s[i],s[i]);i+=1
    elif s[i]=="'":i+=1;break
    else:v+=s[i];i+=1
   out.append(v)
  else:
   j=i
   while s[i] not in ',)':i+=1
   v=s[j:i];out.append(None if v=='NULL' else v)
  if s[i]==')':break
  assert s[i]==',';i+=1
 return out
replacements={};counts={}
for table,e in exports.items():
 schema=re.search(r'CREATE TABLE `'+table+r'` \((.*?)\n\) ENGINE=',text,re.S).group(1)
 columns=re.findall(r'^  `([^`]+)`',schema,re.M)
 assert columns==e['columns'],(table,'schema mismatch')
 replacements[table]={}
 for row in e['rows']:
  line='('+','.join(quote(row[c]) for c in columns)+')'
  assert parse(line)==[None if row[c] is None else str(row[c]) for c in columns]
  replacements[table][str(row['id'])]=line
 counts[table]=0
active=None;seen=set();out=[]
for line in text.splitlines(keepends=True):
 m=re.match(r'INSERT INTO `([^`]+)` VALUES',line)
 if m:active=m[1]
 if active in replacements:
  m=re.match(r'^\((\d+),',line)
  if m and m[1] in replacements[active]:
   key=(active,m[1]);assert key not in seen;seen.add(key)
   assert line.endswith(('),\n',');\n'))
   assert len(parse(line))==len(exports[active]['columns'])
   line=replacements[active][m[1]]+line[-2:]
   counts[active]+=1
 if line.rstrip().endswith(';'):active=None
 out.append(line)
for t in exports:assert counts[t]==len(exports[t]['rows']),(t,counts[t])
new=''.join(out).encode();tmp=path.with_suffix('.sql.tmp');tmp.write_bytes(new);tmp.replace(path)
# Confirm that every line outside the intended 76 rows is byte-identical.
oldlines=raw.splitlines(keepends=True);newlines=new.splitlines(keepends=True)
assert len(oldlines)==len(newlines)
assert sum(a!=b for a,b in zip(oldlines,newlines))==sum(counts.values())
report={'updated_rows':counts,'other_lines_unchanged':True,'sql_sha256':hashlib.sha256(new).hexdigest(),'previous_backup':str(backup)}
(base/'sql-verification.json').write_text(json.dumps(report,indent=2))
print(json.dumps(report,indent=2))
