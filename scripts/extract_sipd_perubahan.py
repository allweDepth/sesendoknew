#!/usr/bin/env python3
"""Read SIPD before/after budget PDFs; fail reconciliation before any DB import."""
import argparse, hashlib, json, re
from pathlib import Path
from decimal import Decimal
import pdfplumber
from extract_sipd_dpa import clean, parse_factors


def amount(value):
    text=clean(value).replace('Rp.','').replace('Rp','').replace('.','').replace(',','.').strip()
    if text in ('','-'): return '0.00'
    return str(Decimal(text).quantize(Decimal('.01')))


def extract(path):
    doc={'source_file':path.name,'sha256':hashlib.sha256(path.read_bytes()).hexdigest(),'items':[],'accounts':{}}
    account=package=group=fund=''
    with pdfplumber.open(path) as pdf:
        for page_no,page in enumerate(pdf.pages,1):
            for table in page.extract_tables():
                if not table:continue
                for r in table:
                    if len(r)==2:
                        key=clean(r[0]);value=clean(r[1]).lstrip(': ').strip()
                        if key in ['Sub Kegiatan','Unit Organisasi','Program','Kegiatan','Lokasi','Sumber Pendanaan','Alokasi 2026']:
                            doc[key]=value
                header=next((i for i,r in enumerate(table) if clean(r[0])=='Kode Rekening' and any('Sesudah' in clean(c) for c in r)),None)
                if header is None:continue
                labels=table[header+1]
                starts=[i for i,c in enumerate(labels) if clean(c)=='Koefisien']
                if len(starts)!=2:raise ValueError((path.name,page_no,'Unknown columns',labels))
                before_i,after_i=starts
                for r in table[header+2:]:
                    code=clean(r[0]);desc=clean(r[1])
                    if re.fullmatch(r'5(?:\.\d+)*',code):
                        account=code
                        doc['accounts'][code]={'uraian':desc,'before':amount(r[before_i+4]),'after':amount(r[after_i+4])}
                        continue
                    if re.match(r'\[\s*#\s*\]',desc):
                        chunks=re.split(r'Sumber Dana\s*:\s*',str(r[1]),maxsplit=1,flags=re.I)
                        package=re.sub(r'^\[\s*#\s*\]\s*','',clean(chunks[0]));fund=clean(chunks[1]) if len(chunks)>1 else '';group='';continue
                    if re.match(r'\[\s*-\s*\]',desc):group=re.sub(r'^\[\s*-\s*\]\s*','',desc);continue
                    if not desc or desc.startswith('Jumlah') or not clean(r[after_i]) or not clean(r[after_i+2]):continue
                    if len(account.split('.'))!=6:raise ValueError((path.name,page_no,'Missing detail account',account,desc))
                    parts=re.split(r'Spesifikasi\s*:\s*',str(r[1]),maxsplit=1,flags=re.I)
                    item={'kd_akun':account,'kelompok':package,'uraian_kelompok':group,'sumber_dana':fund,'komponen':clean(parts[0]),'spesifikasi':clean(parts[1]) if len(parts)>1 else '', 'source_page':page_no}
                    for label,start in [('before',before_i),('after',after_i)]:
                        # SIPD wraps long decimal quantities across lines inside a cell.
                        coef=re.sub(r'(?<=\d)\s+(?=\d)', '', clean(r[start]));unit=clean(r[start+1]);vol,factors,units=parse_factors(coef,unit)
                        item[label]={'koefisien_keterangan':coef,'satuan':unit,'volume':vol,'factors':factors,'factor_units':units,'harga_satuan':amount(r[start+2]),'pajak':amount(r[start+3]),'jumlah':amount(r[start+4])}
                    doc['items'].append(item)
    if 'Sub Kegiatan' not in doc:return None
    m=re.match(r'([\d.]+)\s*(?:-\s*)?(.*)',doc['Sub Kegiatan'])
    doc['sub_kegiatan'],doc['nama_sub_kegiatan']=m.groups()
    for label in ['before','after']:
        total=sum((Decimal(i[label]['jumlah']) for i in doc['items']),Decimal(0))
        expected=Decimal(doc['accounts']['5'][label])
        if total!=expected:raise ValueError(f'{path.name}: {label}: details {total} != root {expected}')
        for code,accountrow in doc['accounts'].items():
            actual=sum((Decimal(i[label]['jumlah']) for i in doc['items'] if i['kd_akun']==code or i['kd_akun'].startswith(code+'.')),Decimal(0))
            if actual!=Decimal(accountrow[label]):raise ValueError(f'{path.name}: {label} {code}: {actual} != {accountrow[label]}')
        doc['total_'+label]=str(total)
    if Decimal(amount(doc['Alokasi 2026']))!=Decimal(doc['total_after']):raise ValueError('Alokasi differs from detail')
    return doc


def extract_recap(path):
    rows={}; hierarchy={}
    with pdfplumber.open(path) as pdf:
        for page in pdf.pages:
            for table in page.extract_tables():
                if not any(clean(r[0])=='Kode' for r in table):continue
                header=next(r for r in table if sum('Jumlah (Rp)' in clean(x) for x in r)==2)
                bi,ai=[i for i,x in enumerate(header) if 'Jumlah (Rp)' in clean(x)]
                for r in table:
                    parts=[clean(x) for x in r[:5]]
                    valid=[x for x in parts if x]
                    if not valid or not all(re.fullmatch(r'\d+(?:\.\d+)?',x) for x in valid):continue
                    code='.'.join(valid)
                    item={'code':code,'name':clean(r[5]),'before':amount(r[bi]),'after':amount(r[ai])}
                    hierarchy[code]=item
                    if all(parts):rows[code]=item
    for label in ['before','after']:
        if sum(Decimal(r[label]) for r in rows.values())!=Decimal(hierarchy['1'][label]):raise ValueError('Recap totals do not reconcile')
    return {'source_file':path.name,'sha256':hashlib.sha256(path.read_bytes()).hexdigest(),'rows':rows,'hierarchy':hierarchy,'total_before':hierarchy['1']['before'],'total_after':hierarchy['1']['after']}


def main():
    parser=argparse.ArgumentParser();parser.add_argument('directory');parser.add_argument('output');args=parser.parse_args()
    docs=[]
    for path in sorted(Path(args.directory).iterdir()):
        if not path.is_file() or not path.read_bytes().startswith(b'%PDF'):continue
        if 'Rekap' in path.name:continue
        d=extract(path)
        if d:docs.append(d);print(d['sub_kegiatan'],len(d['items']),d['total_before'],d['total_after'],flush=True)
    unique={}
    for doc in docs:
        code=doc['sub_kegiatan']
        if code in unique:
            prior=unique[code]
            if doc['items']!=prior['items'] or doc['accounts']!=prior['accounts']:raise ValueError('Conflicting duplicate subactivity PDF: '+code)
            prior.setdefault('duplicate_files',[]).append({'source_file':doc['source_file'],'sha256':doc['sha256']})
        else:unique[code]=doc
    docs=list(unique.values())
    recap=extract_recap(next(Path(args.directory).glob('*Rekap*.pdf')))
    for doc in docs:
        expected=recap['rows'][doc['sub_kegiatan']]
        for label in ['before','after']:
            if doc['total_'+label]!=expected[label]:raise ValueError('Detail and recap differ: '+doc['sub_kegiatan'])
    payload={'recap':recap,'tahun':2026,'kd_wilayah':'76.01','kd_opd':'1.03.0.00.0.00.01.0000','documents':docs}
    Path(args.output).write_text(json.dumps(payload,ensure_ascii=False,indent=2))

if __name__=='__main__':main()
