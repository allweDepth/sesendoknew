const fs=require('fs'),assert=require('assert');
const {chromium}=require('/Users/nabiila_inayah/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async()=>{
const browser=await chromium.launch({executablePath:'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',headless:true});
try{
const page=await browser.newPage({viewport:{width:1400,height:1000}});
await page.setContent('<div id="procurementEditorModal"><div id="procurementParagraphEditor"></div></div>');
await page.addStyleTag({path:'public/assets/vendor/fomantic-ui/semantic.min.css'});
await page.addScriptTag({path:'public/assets/vendor/jquery/jquery.min.js'});
await page.evaluate(()=>window.BaseCrudModule=class{});
await page.addScriptTag({content:fs.readFileSync('public/assets/js/modules/kontrak.js','utf8')+';window.EditorClass=KontrakModule;'});
const catalog=JSON.parse(fs.readFileSync('resources/procurement/swakelola/catalog.json','utf8'));
const tables=[];function walk(x){if(Array.isArray(x))x.forEach(walk);else if(x&&typeof x==='object'){if(x.type==='table')tables.push(x);Object.values(x).forEach(walk);}}walk(catalog);
const no=tables.find(t=>/^No\.?$/i.test(t.rows[0]?.[0]?.blocks[0]?.text.trim()||''));assert(no,'source No table');
await page.evaluate(no=>{
 window.module=Object.create(EditorClass.prototype);module.esc=t=>String(t).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('"','&quot;');
 const p=text=>({type:'paragraph',text});
 module.renderSwakelolaEditor([{kode_bagian:'TEST',judul:'Tabel',isi:JSON.stringify([p('Paket Pengadaan      : Jalan'),p('Tahun Anggaran\t\t: 2026'),no,{type:'table',rows:[[{blocks:[p('→')]},{blocks:[p('Detil pekerjaan dalam KAK')]}]]},{type:'table',rows:[[{blocks:[p('6. Pengguna')]},{blocks:[p('• Nama Satuan Kerja/Perangkat Daerah : Dinas Pekerjaan Umum dan Penataan Ruang'),p('• Nama PPK : Syamsunar, SP.,M.M'),p('• Alamat PPK : Jalan Pangeran Antasari Pasangkayu'),p('• E-mail PPK : —'),p('• Website Satuan Kerja/Kementerian/Lembaga/Perangkat Daerah: —')]}]]}])}]);
},no);
assert.equal(await page.locator('table').nth(0).locator('col').first().evaluate(e=>e.style.width),'38%');
const table=page.locator('table').nth(1);assert.equal(await table.locator('col').first().evaluate(e=>e.style.width),'6%');
assert.equal(await page.locator('table').nth(2).locator('td').first().evaluate(e=>getComputedStyle(e).borderTopWidth),'0px');
const identity=page.locator('table.sw-borderless').last();
assert.equal(await identity.locator('tr').count(),5);
const colonX=await identity.locator('tr').evaluateAll(rows=>rows.map(r=>r.cells[2].getBoundingClientRect().x));
assert(colonX.every(x=>Math.abs(x-colonX[0])<1));
// Editing a paragraph, resizing a merged-header table, then inserting a column retains text and widths.
const paragraph=table.locator('[data-sw-node]').last();await paragraph.fill('Isi yang harus tetap tersimpan');
const handle=table.locator('[data-sw-boundary="1"]').first(),box=await handle.boundingBox();
await page.mouse.move(box.x+4,box.y+10);await page.mouse.down();await page.mouse.move(box.x+44,box.y+10);await page.mouse.up();
const width=await table.locator('col').first().evaluate(e=>parseFloat(e.style.width));assert(width>6);
await table.locator('td').last().click();
await page.locator('.sw-toolbar [data-sw-table-action="row-after"]').click();
assert(await page.locator('p').filter({hasText:'Isi yang harus tetap tersimpan'}).count());
await page.locator('table').nth(1).locator('td').last().click();await page.locator('.sw-toolbar [data-sw-table-action="col-before"]').click();
await page.locator('table').nth(1).locator('tr').last().locator('td').nth(-2).click();await page.locator('.sw-toolbar [data-sw-table-action="col-delete"]').click();
await page.locator('table').nth(1).locator('td').last().click();await page.locator('.sw-toolbar [data-sw-table-action="row-delete"]').click();
// Native contextmenu must not be cancelled by the companion application tools.
assert.equal(await page.locator('table').nth(1).locator('td').last().evaluate(td=>td.dispatchEvent(new MouseEvent('contextmenu',{bubbles:true,cancelable:true,clientX:400,clientY:400}))),true);
// Context menu uses the right-clicked cell, not a previous toolbar selection.
await page.locator('table').nth(1).locator('td').last().click({button:'right'});
assert(await page.locator('.sw-context-menu').isVisible());
await page.locator('.sw-context-menu [data-sw-table-action="cell-before"]').click();
await page.locator('table').nth(1).locator('tr').last().locator('td').nth(-2).click({button:'right'});
await page.locator('.sw-context-menu [data-sw-table-action="cell-delete"]').click();
await page.locator('table').nth(1).locator('td').first().click({button:'right'});await page.keyboard.press('Escape');
assert(!(await page.locator('.sw-context-menu').isVisible()));
const sections=await page.evaluate(()=>module.collectSwakelolaSections());
const blocks=JSON.parse(sections[0].isi);assert(Math.abs(blocks[1].column_widths.reduce((a,b)=>a+b,0)-100)<1e-8);
await page.evaluate(sections=>module.renderSwakelolaEditor(sections),sections);
assert(await page.locator('p').filter({hasText:'Isi yang harus tetap tersimpan'}).count());
// Merged headers must remain rectangular after insertion/deletion at a body-column boundary.
await page.evaluate(()=>{
 const p={type:'paragraph',text:''},c=(colspan=1)=>({colspan,blocks:[{...p}]}),t={column_widths:[20,20,20,20,20],rows:[[c(),c(2),c(),c()],[c(),c(),c(),c(),c()]]};
 module.editSwakelolaTable(t,1,2,'col-before');if(t.rows[0][1].colspan!==3)throw Error('merged insertion');
 module.editSwakelolaTable(t,1,2,'col-delete');if(t.rows[0][1].colspan!==2)throw Error('merged deletion');
 for(const row of t.rows)if(row.reduce((n,c)=>n+(c.colspan||1),0)!==t.column_widths.length)throw Error('grid mismatch');
});
// Blank paragraphs inside signature cells can be removed and Enter splits at the caret.
await page.evaluate(()=>{
 module.collectSwakelolaSections();const p=text=>({type:'paragraph',text});
 module.swakelolaSections[0].blocks.push({type:'table',rows:[[{blocks:[p('Penyusun:'),p(''),p('Nama Lengkap')]},{blocks:[p('Ditetapkan oleh')]}]]},p('• Butir bullet dengan teks lanjutan yang harus sejajar.'),p('1. Paragraf bernomor dengan uraian yang cukup panjang untuk menguji kesejajaran baris lanjutan di dalam dokumen.'));
 module.refreshSwakelolaEditor();
});
const signature=page.locator('table').last(),left=signature.locator('td').first();
await left.locator('[data-sw-node]').nth(1).focus();await page.keyboard.press('Backspace');
assert.equal(await page.locator('table').last().locator('td').first().locator('[data-sw-node]').count(),2);
const name=page.locator('[data-sw-node]').filter({hasText:/^Nama Lengkap$/});
await name.evaluate(e=>{e.focus();const range=document.createRange();range.setStart(e.firstChild,4);range.collapse(true);const selection=getSelection();selection.removeAllRanges();selection.addRange(range);});
await page.keyboard.press('Enter');
assert.equal(await page.locator('table').last().locator('td').first().locator('[data-sw-node]').count(),3);
await page.keyboard.press('Backspace');
assert.equal(await page.locator('[data-sw-node]').filter({hasText:/^Nama Lengkap$/}).count(),1);
await page.locator('.sw-toolbar [data-sw-paragraph-action="after"]').click();
assert.equal(await page.locator('table').last().locator('td').first().locator('[data-sw-node]').count(),3);
await page.locator('.sw-toolbar [data-sw-paragraph-action="delete"]').click();
assert.equal(await page.locator('table').last().locator('td').first().locator('[data-sw-node]').count(),2);
const hang=await page.locator('[data-sw-node]').filter({hasText:/^• Butir bullet/}).evaluate(e=>({padding:parseFloat(e.style.paddingLeft),indent:parseFloat(e.style.textIndent)}));
assert(hang.padding>0&&hang.padding===-hang.indent);
// Check actual wrapped-line geometry for each marker family, including source tabs.
await page.evaluate(()=>{
 const markers=['•','a.','1.','1)','(a)','(1)','IV.','1.2.','–'];
 module.collectSwakelolaSections();markers.forEach(marker=>module.swakelolaSections[0].blocks.push({type:'paragraph',text:marker+' \tKalimat panjang untuk memastikan bahwa semua baris lanjutan sejajar dengan awal teks setelah penanda.'}));module.refreshSwakelolaEditor();
 const nodes=Array.from(document.querySelectorAll('[data-sw-node]')).slice(-markers.length);
 nodes.forEach(node=>{node.style.width='220px';node.dispatchEvent(new Event('input',{bubbles:true}));const text=node.firstChild,prefix=node.innerText.indexOf('Kalimat'),range=document.createRange();range.setStart(text,prefix);range.setEnd(text,text.length);const rects=Array.from(range.getClientRects()).filter(r=>r.width>10);if(rects.length<2||rects.some(r=>Math.abs(r.x-rects[0].x)>1))throw Error('Hanging indent mismatch '+JSON.stringify({text:node.innerText.slice(0,prefix),padding:node.style.paddingLeft,indent:node.style.textIndent,rects:rects.map(r=>({x:r.x,w:r.width}))}));});
});
const finalSections=await page.evaluate(()=>module.collectSwakelolaSections());
await page.evaluate(sections=>module.renderSwakelolaEditor(sections),finalSections);
assert.equal(await page.locator('[data-sw-node]').filter({hasText:/^Nama Lengkap$/}).count(),1);
fs.mkdirSync('outputs/swakelola-editor-20261101',{recursive:true});
fs.writeFileSync('outputs/swakelola-editor-20261101/sections.json',JSON.stringify(finalSections));
await page.screenshot({path:'outputs/swakelola-editor-20261101/editor.png',fullPage:true});
console.log('PASS: browser drag, row/column CRUD, merged cells, text retention, save/reopen serialization, colon layout and borderless note');
}finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
