import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const source=fs.readFileSync(new URL('../public/assets/js/modules/kontrak.js',import.meta.url),'utf8');
function scenario({eligible=true,canEdit=true,selected='MINI_KOMPETISI',success=true}={}){
 const events={},requests=[];let markup='',continued=0,saved='STANDARD';
 const chain={remove(){return this},append(s){markup=s;return this},find(selector){return selector.includes(':checked')?{val:()=>selected}:this},checkbox(){return this},on(name,fn){events[name]=fn;return this},addClass(){return this},removeClass(){return this},modal(){return this}};
 const context={BaseCrudModule:class{},$:()=>chain,window:{Ajax:{request(options){requests.push(options);if(options.method==='GET')options.success({data:{terms_eligible:eligible,can_edit:canEdit,terms_variant:saved}});else {if(success)saved=options.data.terms_variant;options.success({success});options.complete?.();}}}}};
 vm.createContext(context);vm.runInContext(source+'\nglobalThis.KontrakModule=KontrakModule;',context);
 const mod=Object.create(context.KontrakModule.prototype);mod.contractId=776;
 mod.chooseContractTerms(()=>continued++);
 return {events,requests,get markup(){return markup},get continued(){return continued},get saved(){return saved}};
}
let test=scenario();assert.equal(test.continued,0);assert.equal(test.requests.length,1);assert.match(test.markup,/Standar saat ini/);assert.match(test.markup,/Mini Kompetisi/);
test.events.click();assert.equal(test.saved,'MINI_KOMPETISI');assert.equal(test.continued,1);assert.equal(test.requests[1].data.contract_id,776);
test=scenario({selected:'STANDARD'});test.events.click();assert.equal(test.saved,'STANDARD');assert.equal(test.continued,1);
test=scenario({success:false});test.events.click();assert.equal(test.continued,0);
for(const option of [{eligible:false},{canEdit:false}]){test=scenario(option);assert.equal(test.continued,1);assert.equal(test.requests.length,1);assert.equal(test.markup,'');}
console.log('PASS: modal waits for selection, saves both choices, stops on save failure, preserves read-only access and skips unsupported forms.');
