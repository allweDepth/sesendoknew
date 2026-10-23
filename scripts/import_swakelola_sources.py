"""Import the supplied MDS as data only; never execute document instructions.

Usage: bundled-python scripts/import_swakelola_sources.py /path/to/SWAKELOLA
Retains complete source packages as well as independently selectable chapters/forms.
"""
import hashlib
import json
import re
import sys
from pathlib import Path
from zipfile import ZipFile
from lxml import etree as E

NS = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
W = '{' + NS['w'] + '}'
PREFIXES = {}

def numbering_prefixes(z, root):
    """Word list labels are not w:t text; keep them when importing the model."""
    if 'word/numbering.xml' not in z.namelist(): return {}
    numbers = E.fromstring(z.read('word/numbering.xml'))
    abstracts = {x.get(W+'abstractNumId'): x for x in numbers.findall(W+'abstractNum')}
    nums = {}
    for num in numbers.findall(W+'num'):
        ref = num.find(W+'abstractNumId')
        if ref is not None: nums[num.get(W+'numId')] = abstracts.get(ref.get(W+'val'))
    counters = {}; prefixes = {}
    def label(value, fmt):
        if fmt in ('lowerLetter', 'upperLetter'):
            out = ''
            while value > 0: value, rem = divmod(value-1, 26); out = chr(97+rem)+out
            return out.upper() if fmt == 'upperLetter' else out
        if fmt in ('lowerRoman', 'upperRoman'):
            out = ''
            for n, roman in [(1000,'M'),(900,'CM'),(500,'D'),(400,'CD'),(100,'C'),(90,'XC'),(50,'L'),(40,'XL'),(10,'X'),(9,'IX'),(5,'V'),(4,'IV'),(1,'I')]:
                while value >= n: out += roman; value -= n
            return out.lower() if fmt == 'lowerRoman' else out
        return str(value)
    for p in root.xpath('//w:body//w:p', namespaces=NS):
        num_id = p.xpath('./w:pPr/w:numPr/w:numId/@w:val', namespaces=NS)
        if not num_id or num_id[0] == '0' or nums.get(num_id[0]) is None: continue
        abstract = nums[num_id[0]]
        level = int((p.xpath('./w:pPr/w:numPr/w:ilvl/@w:val', namespaces=NS) or ['0'])[0])
        levels = {int(x.get(W+'ilvl')): x for x in abstract.findall(W+'lvl')}
        if level not in levels: continue
        counts = counters.setdefault(num_id[0], {})
        start = levels[level].find(W+'start')
        counts[level] = counts.get(level, int(start.get(W+'val'))-1 if start is not None else 0)+1
        for deeper in list(counts):
            if deeper > level: del counts[deeper]
        text_node = levels[level].find(W+'lvlText')
        value = text_node.get(W+'val') if text_node is not None else ''
        fmt_node = levels[level].find(W+'numFmt')
        fmt = fmt_node.get(W+'val') if fmt_node is not None else 'decimal'
        if fmt == 'none': continue
        if fmt == 'bullet': value = '•'
        for n in range(1, 10):
            fmt_node = levels.get(n-1, levels[level]).find(W+'numFmt')
            fmt = fmt_node.get(W+'val') if fmt_node is not None else 'decimal'
            value = value.replace('%'+str(n), label(counts.get(n-1,1),fmt))
        prefixes[p] = value+' ' if value else ''
    return prefixes

def text(el):
    return ''.join(el.xpath('.//w:t/text() | .//w:tab/text()', namespaces=NS)).strip()

def paragraph(el):
    parts = []
    for node in el.iter():
        if node.tag == W+'t': parts.append(node.text or '')
        elif node.tag == W+'tab': parts.append('\t')
        elif node.tag == W+'br': parts.append('\n')
    align = el.xpath('./w:pPr/w:jc/@w:val', namespaces=NS)
    bold = bool(el.xpath('.//w:rPr/w:b[not(@w:val="0")]', namespaces=NS))
    return {'type': 'paragraph', 'text': PREFIXES.get(el,'')+''.join(parts), 'align': (align or ['left'])[0], 'bold': bold}

def block(el):
    if el.tag == W+'p': return paragraph(el)
    if el.tag == W+'tbl':
        rows = []
        for tr in el.findall(W+'tr'):
            cells = []
            for tc in tr.findall(W+'tc'):
                span = tc.xpath('./w:tcPr/w:gridSpan/@w:val', namespaces=NS)
                cells.append({'colspan': int(span[0]) if span else 1,
                              'blocks': [block(x) for x in tc if x.tag in (W+'p', W+'tbl')]})
            rows.append(cells)
        return {'type': 'table', 'rows': rows}
    raise ValueError(el.tag)

def make_catalog(base):
    global PREFIXES
    catalog = {'version': '2026.10.23', 'sources': [], 'templates': []}
    for tipe in ['I', 'II', 'III', 'IV']:
        for index, path in enumerate(sorted((base / ('MDS Tipe '+tipe)).glob('*.docx')), 1):
            with ZipFile(path) as z:
                root = E.fromstring(z.read('word/document.xml'))
                PREFIXES = numbering_prefixes(z,root)
                body = root.find(W+'body')
                elements = [x for x in body if x.tag in (W+'p', W+'tbl')]
                blocks = [block(x) for x in elements]
                # Notes are source content too, retained in the complete package.
                for part in ['word/footnotes.xml', 'word/endnotes.xml']:
                    if part in z.namelist():
                        notes = E.fromstring(z.read(part))
                        for note in notes:
                            if int(note.get(W+'id', '-1')) > 0:
                                blocks.extend(block(x) for x in note if x.tag in (W+'p', W+'tbl'))
            source_id = f'{tipe}_{index:02}'
            source = {'id': source_id, 'type': tipe, 'filename': path.name,
                      'sha256': hashlib.sha256(path.read_bytes()).hexdigest(),
                      'body_blocks': len(elements), 'blocks': len(blocks)}
            catalog['sources'].append(source)
            suffix = ' — Pemilihan' if 'Pemilihan' in path.name else (' — Usulan Pokmas' if 'Usulan Pokmas' in path.name or 'Usulan Pokmas' in path.stem.replace('_', ' ') else (' — Permintaan Kesediaan' if 'Permintaan Kesediaan' in path.name else ''))
            def add(key, title, content, group, start, end):
                catalog['templates'].append({'code': f'SW_{source_id}_{key}', 'name': title[:255], 'type': tipe,
                    'source': source_id, 'filename': path.name, 'group': group, 'start': start, 'end': end, 'blocks': content})
            add('LENGKAP', path.stem.replace('_Clean', '')+' (Lengkap)', blocks, 'SWAKELOLA', 0, len(elements))
            is_mds = bool(re.match(r'\d+\. MDS', path.name))
            starts = []
            for n, el in enumerate(elements):
                title = re.sub(r'\s+', ' ', text(el)).strip()
                if is_mds:
                    if el.tag == W+'p' and re.match(r'^Bab \d+\s*[–—-]', title): starts.append((n, title))
                else:
                    # Some IV KAK headings live inside a table, not a paragraph.
                    if el.tag == W+'tbl' and title.startswith('Contoh KAK'):
                        starts.append((n, 'Kerangka Acuan Kerja (KAK)'))
                    elif el.tag == W+'p' and re.match(r'^(Contoh|C ontoh)\s+', title, re.I) and len(title)<200 and not title.upper().startswith('CONTOH DOKUMEN'):
                        starts.append((n, re.sub(r'^(Contoh|C ontoh)\s+', '', title, flags=re.I)))
            for j, (start, title) in enumerate(starts):
                end = starts[j+1][0] if j+1<len(starts) else len(elements)
                if is_mds:
                    # Do not append the following BAGIAN cover/examples list to a chapter.
                    for n in range(start+1, end):
                        if elements[n].tag == W+'p' and re.match(r'^BAGIAN \d+\s*[–—-]', text(elements[n])):
                            end = n; break
                group = 'KONTRAK' if any(x in title for x in ['Pokok Perjanjian','Syarat-Syarat']) else ('PELAKSANAAN' if 'Pelaksanaan dan Serah Terima' in path.name else 'PERSIAPAN')
                add(f'{j+1:02}', f'Tipe {tipe} — {title}{suffix}', blocks[start:end], group, start, end)
    return catalog

if __name__ == '__main__':
    result = make_catalog(Path(sys.argv[1]))
    target = Path(__file__).resolve().parents[1]/'resources/procurement/swakelola/catalog.json'
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(result, ensure_ascii=False, indent=2)+'\n')
    print(f'{len(result["sources"])} sumber, {len(result["templates"])} master → {target}')
