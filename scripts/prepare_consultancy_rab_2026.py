"""Read source workbooks and complete amendment consultancy RAB through Rp100m."""
import argparse,json,hashlib
from decimal import Decimal,ROUND_HALF_UP,ROUND_DOWN
from pathlib import Path
from openpyxl import load_workbook
ap=argparse.ArgumentParser();ap.add_argument('manifest');ap.add_argument('template');ap.add_argument('output');args=ap.parse_args()
d=json.loads(Path(args.manifest).read_text());s=load_workbook(args.template,data_only=True).worksheets[0]
money=lambda n:str(Decimal(n).quantize(Decimal('.01'),rounding=ROUND_HALF_UP))
count=0
for c in d['contracts']:
 if not c['jenis_pengadaan'].startswith('KONSULTANSI') or Decimal(c['budget'])>100000000:continue
 if not c.get('rab_template'):
  factor=Decimal(c['budget'])/Decimal(100000000);rows=[]
  for i in range(4,11):
   r={'nomor':str(len(rows)+1),'uraian':s.cell(i,2).value,'satuan':s.cell(i,7).value,'pajak':str(s.cell(i,11).value or 0)}
   for k,j,q in [('hps',8,4),('penawaran',9,5),('negoisasi',10,6)]:
    r['vol_'+k]=str(s.cell(i,q).value);r['harga_sat_'+k]=money(Decimal(str(s.cell(i,j).value))*factor);r['jumlah_'+k]=money(Decimal(r['vol_'+k])*Decimal(r['harga_sat_'+k])*(1+Decimal(r['pajak'])/100))
   rows.append(r)
  c['rab']=rows;c['rab_template']='HPS dan penawaran konsultan 2026.xlsx / 64449217'
 for r in c['rab']:
  for k in ['hps','penawaran','negoisasi']:r.setdefault('vol_'+k,'1')
 total=sum(Decimal(r['jumlah_negoisasi']) for r in c['rab']);target=(total/1000).to_integral_value(rounding=ROUND_DOWN)*1000;reduction=total-target
 r=max(c['rab'],key=lambda r:Decimal(r['jumlah_negoisasi']));factor=Decimal(r['vol_negoisasi'])*(1+Decimal(r['pajak'])/100)
 r['harga_sat_negoisasi']=str((Decimal(r['harga_sat_negoisasi'])-reduction/factor).quantize(Decimal('.000000000001'),rounding=ROUND_HALF_UP));r['jumlah_negoisasi']=money(Decimal(r['harga_sat_negoisasi'])*factor)
 totals={k:sum(Decimal(r['jumlah_'+k]) for r in c['rab']) for k in ['hps','penawaran','negoisasi']}
 if totals['negoisasi']!=target:
  correction=totals['negoisasi']-target;r['harga_sat_negoisasi']=str((Decimal(r['harga_sat_negoisasi'])-correction/factor).quantize(Decimal('.000000000001'),rounding=ROUND_HALF_UP));r['jumlah_negoisasi']=money(Decimal(r['harga_sat_negoisasi'])*factor)
  totals['negoisasi']=sum(Decimal(x['jumlah_negoisasi']) for x in c['rab'])
 assert totals['negoisasi']==target,(c['number'],totals['negoisasi'],target)
 assert 0<target<=totals['penawaran']<totals['hps']<=Decimal(c['budget'])
 c['nilai_hps']=money(totals['hps']);c['nilai_kontrak']=money(target);c['rab_note']='Draft estimasi proporsional; negosiasi dibulatkan ke bawah Rp1.000 melalui penyesuaian komponen terbesar.';count+=1
d['planning_template']=str(Path(args.template).resolve());d['planning_template_sha256']=hashlib.sha256(Path(args.template).read_bytes()).hexdigest()
Path(args.output).write_text(json.dumps(d,ensure_ascii=False,indent=2));print(json.dumps({'consultancy_contracts':count,'rab_rows':sum(len(c['rab']) for c in d['contracts'])}))
