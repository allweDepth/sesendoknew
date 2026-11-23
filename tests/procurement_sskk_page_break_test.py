"""Check actual PDF geometry: no open borders or stretched empty row tails."""
import json
import subprocess
from pathlib import Path
import pdfplumber

root = Path(__file__).resolve().parents[1]
files = json.loads(subprocess.check_output(['php', str(root / 'tests/procurement_sskk_page_break_fixture.php')], cwd=root))
try:
    for variant, path in files.items():
        with pdfplumber.open(path) as pdf:
            assert len(pdf.pages) > 1
            content = []
            for number, page in enumerate(pdf.pages, 1):
                vertical = [e for e in page.edges if e['orientation'] == 'v' and e['height'] > 2]
                horizontal = [e for e in page.edges if e['orientation'] == 'h' and e['width'] > 10]
                words = page.extract_words()
                assert vertical and horizontal and words, (variant, number, 'empty table page')
                border_bottom = max(e['bottom'] for e in horizontal)
                assert max(e['bottom'] for e in vertical) - border_bottom < 1.5, (variant, number, 'open border tail')
                assert border_bottom - max(w['bottom'] for w in words) < 16, (variant, number, 'stretched empty cell')
                assert 'Pasal' in page.extract_text() and 'Ketentuan' in page.extract_text(), (variant, number, 'missing repeated header')
                content.append(page.extract_text())
            assert 'Umur Konstruksi' in ' '.join(content)
            print(f'PASS: {variant}: {len(pdf.pages)} pages, closed rows, no empty cell tails, repeated headers.')
finally:
    for path in files.values():
        Path(path).unlink(missing_ok=True)
        Path(path.removesuffix('.pdf')).unlink(missing_ok=True)
