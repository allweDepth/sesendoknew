#!/usr/bin/env python3
"""Ekstrak DPA rincian belanja SIPD menjadi JSON terstruktur.

Baris [#] dipertahankan sebagai kelompok/paket dan [-] sebagai uraian kelompok.
Tidak ada teks instruksi di PDF yang dieksekusi; seluruh isi diperlakukan sebagai data.
"""
from __future__ import annotations
import argparse, json, re
from pathlib import Path
import pdfplumber

SPACE = re.compile(r"\s+")
CODE = re.compile(r"^5(?:\.\d+){1,5}$")

def clean(value):
    return SPACE.sub(" ", str(value or "").replace("\u00a0", " ")).strip()

def money(value):
    text=clean(value).replace("Rp", "").replace(".", "").replace(",", ".")
    text=re.sub(r"[^0-9.-]", "", text)
    return float(text or 0)

def number(value):
    text=clean(value).replace(",", ".")
    found=re.search(r"-?\d+(?:\.\d+)?", text)
    return float(found.group()) if found else 0.0

def split_description(value):
    raw=str(value or "").replace("\u00a0", " ")
    parts=re.split(r"\n?Spesifikasi:\s*", raw, maxsplit=1, flags=re.I)
    return clean(parts[0]), clean(parts[1]) if len(parts)>1 else ""

def parse_factors(coefficient, unit):
    raw=clean(coefficient)
    factors=[]
    for part in re.split(r"\s+[xX]\s+", raw):
        match=re.match(r"\s*(-?\d+(?:[.,]\d+)?)\s*(.*)", part)
        if not match: continue
        factors.append((float(match.group(1).replace(",", ".")), clean(match.group(2))))
    volume=1.0
    for value,_ in factors: volume*=value
    if not factors: volume=number(raw)
    units=[u for _,u in factors]
    if not units and clean(unit): units=[clean(unit)]
    return volume, ([v for v,_ in factors]+[0]*5)[:5], (units+[""]*5)[:5]

def extract_pdf(path: Path):
    result={"source_file":path.name,"sub_kegiatan":"","program":"","kegiatan":"","lokasi":"","indikator":"","target":"","sumber_pendanaan":"","items":[],"monthly":{}}
    account=""; package=""; detail_group=""; fund=""
    with pdfplumber.open(path) as document:
        full_text="\n".join(page.extract_text() or "" for page in document.pages)
        for pattern,key in [(r"Sub Kegiatan\s*:\s*([\d.]+)\s*-\s*([^\n]+)","sub"),(r"Program\s*:\s*([^\n]+)","program"),(r"Kegiatan\s*:\s*([^\n]+)","kegiatan"),(r"Lokasi\s*:\s*([^\n]+)","lokasi"),(r"Keluaran Sub Kegiatan\s*:\s*([^\n]+)","indikator")]:
            found=re.search(pattern,full_text,re.I)
            if found:
                if key=="sub": result["sub_kegiatan"],result["nama_sub_kegiatan"]=found.group(1),clean(found.group(2))
                else: result[key]=clean(found.group(1))
        funding=re.search(r"Sumber Pendanaan\s*:\s*(.*?)(?:\nLokasi\s*:)",full_text,re.I|re.S)
        if funding: result["sumber_pendanaan"]=clean(funding.group(1).replace("\n:","; "))
        target=re.search(r"Keluaran Sub Kegiatan\s*:\s*[^\n]+.*?Target Kinerja\s*:?\s*([^\n]+)",full_text,re.I|re.S)
        if target: result["target"]=clean(target.group(1))
        months=["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"]
        for index,name in enumerate(months,1):
            found=re.search(rf"\b{name}\s+Rp([\d.]+,\d{{2}})",full_text,re.I)
            if found: result["monthly"][str(index)]=money(found.group(1))

        for page in document.pages:
            for table in page.extract_tables():
                if not table: continue
                header=next((i for i,row in enumerate(table) if clean(row[0] if row else "")=="Kode Rekening"),None)
                if header is None: continue
                # Lebar grid SIPD bervariasi antar halaman. Cari posisi kolom
                # dari label header supaya baris komponen tidak bergeser.
                width=max(len(row or []) for row in table)
                header_rows=table[header:min(header+3,len(table))]
                def column(label, fallback):
                    for header_row in header_rows:
                        for idx,value in enumerate(header_row or []):
                            if label in clean(value).lower(): return idx
                    return fallback
                desc_i=column("uraian",1)
                coeff_i=column("koefisien",4 if width>=9 else 2)
                unit_i=column("satuan",5 if width>=9 else 3)
                price_i=column("harga",6 if width>=9 else 4)
                tax_i=column("ppn",7 if width>=9 else 5)
                amount_i=column("jumlah",width-1)
                for row in table[header+1:]:
                    if not row or len(row)<=amount_i: continue
                    code=clean(row[0]); desc=clean(row[desc_i]); coeff=clean(row[coeff_i]); price=clean(row[price_i]); amount=clean(row[amount_i])
                    if CODE.match(code): account=code
                    if desc.startswith("[ # ]"):
                        raw=str(row[desc_i] or ""); chunks=re.split(r"\nSumber Dana:\s*",raw,maxsplit=1,flags=re.I)
                        package=clean(chunks[0].replace("[ # ]","",1)); fund=clean(chunks[1]) if len(chunks)>1 else ""; detail_group=""; continue
                    if desc.startswith("[ - ]"):
                        detail_group=clean(desc.replace("[ - ]","",1)); continue
                    if not account or not coeff or not price or not amount or desc in ("Uraian","Rincian Perhitungan"): continue
                    component,specification=split_description(row[desc_i]); volume,factor_values,factor_units=parse_factors(coeff,row[unit_i])
                    result["items"].append({"kd_akun":account,"jenis_kelompok":"pemaketan","kelompok":package,"uraian_kelompok":detail_group,"sumber_dana":fund,"komponen":component,"spesifikasi":specification,"koefisien_keterangan":coeff,"factors":factor_values,"factor_units":factor_units,"volume":volume,"satuan":clean(row[unit_i]),"harga_satuan":money(price),"pajak":number(row[tax_i]),"jumlah":money(amount)})
    if not result["sub_kegiatan"]: return None
    result["total_items"]=len(result["items"]);result["total_amount"]=sum(x["jumlah"] for x in result["items"])
    return result

def main():
    parser=argparse.ArgumentParser();parser.add_argument("input_dir");parser.add_argument("output_json")
    args=parser.parse_args(); docs=[]
    for path in sorted(Path(args.input_dir).glob("*.pdf")):
        if not re.match(r"^1\.03\.",path.name): continue
        parsed=extract_pdf(path)
        if parsed: docs.append(parsed)
    payload={"format":"SIPD DPA Rincian Belanja","tahun":2026,"kd_wilayah":"76.01","kd_opd":"1.03.0.00.0.00.01.0000","documents":docs,"total_items":sum(x["total_items"] for x in docs),"total_amount":sum(x["total_amount"] for x in docs)}
    Path(args.output_json).write_text(json.dumps(payload,ensure_ascii=False,indent=2),encoding="utf-8")
    print(json.dumps({"documents":len(docs),"items":payload["total_items"],"amount":payload["total_amount"]},ensure_ascii=False))
if __name__=="__main__": main()
