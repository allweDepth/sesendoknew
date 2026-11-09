const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../public/assets/js/modules/kontrak.js','utf8');
const method=source.slice(source.indexOf('\tasync printOffer('),source.indexOf('\n\tloadProcurementDocuments()',source.indexOf('\tasync printOffer(')));
async function run(kind,{valid=true,manage=true,saveFails=false}={}) {
 const requests=[],downloads=[],classes=new Set();let reported=0,error='';
 const nativeForm={checkValidity:()=>valid,reportValidity:()=>reported++};
 const chain={removeClass(){return this},remove(){},text(v){error=v;return this},closest(){return this},addClass(){return this},appendTo(){}};
 const buttons={hasClass:c=>classes.has(c),addClass(s){s.split(' ').forEach(c=>classes.add(c))},removeClass(s){s.split(' ').forEach(c=>classes.delete(c))}};
 // The visible panel is a DIV inside the shared sidebar FORM.
 const panel={0:{tagName:'DIV'},is:()=>false,closest:()=>({0:nativeForm}),removeClass(){return this},addClass(){return this},find:s=>s==='[data-offer-print]'?buttons:chain};
 const context={
  $:s=>s==='#contractOfferPrintForm'?panel:chain,
  window:{CSRF_TOKEN:'token',appUrl:p=>p},
  FormData:class {constructor(form){assert.equal(form,nativeForm)}set(){}},
  fetch:async(url,options)=>{requests.push({url,options});return url.endsWith('/save')?{ok:!saveFails,json:async()=>({success:!saveFails,message:'Save failed',data:{}})}:{ok:true,headers:{get:()=> 'application/pdf'},blob:async()=>({})}},
  URL:{createObjectURL:()=> 'blob:pdf',revokeObjectURL(){}},
  document:{body:{append(){}},createElement:()=>({click(){downloads.push(this.download)},remove(){}})},setTimeout(){}
 };
 vm.createContext(context);vm.runInContext('globalThis.module={contractId:820,'+method.trim()+'}',context);
 await context.module.printOffer(kind,manage);
 assert.equal(classes.size,0,'Loading state must always reset');
 if(manage&&!valid){assert.equal(reported,1);assert.equal(requests.length,0);assert.equal(downloads.length,0)}
 else if(saveFails){assert.equal(requests.length,1);assert.equal(downloads.length,0);assert.equal(error,'Save failed')}
 else {assert.equal(requests.length,manage?2:1);assert.ok(requests.at(-1).url.endsWith('&kind='+kind));assert.deepEqual(downloads,[`penawaran-${kind}-820.pdf`])}
}
(async()=>{for(const kind of ['bundle','letter','rab'])await run(kind);await run('bundle',{valid:false});await run('letter',{saveFails:true});await run('rab',{manage:false});console.log('PASS: Three offer buttons, native form validation, save failure, read-only printing, loading cleanup.');})().catch(e=>{console.error(e);process.exitCode=1});
