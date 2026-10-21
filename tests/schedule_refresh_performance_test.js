const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../public/assets/js/app-init.js','utf8');
const block=source.slice(source.indexOf('    const scheduleStages ='),source.indexOf('\n\tconst $context ='));
let now=1000000, requests=[], timers=[], listeners={};
const empty={length:0,data:()=>undefined,each:()=>{}};
const context={
    $:()=>empty,
    window:{APP_BASE_PATH:'/sesendoknew',app:{user:{type_user:'admin_opd'},state:{tbl:'asn'}},Ajax:{request:options=>{requests.push(options);return {};}},addEventListener:(name,fn)=>{listeners[name]=fn;}},
    location:{pathname:'/sesendoknew/kepegawaian',search:''},
    document:{hidden:false,addEventListener:(name,fn)=>{listeners[name]=fn;}},
    URLSearchParams,Date:class extends Date{static now(){return now;}},
    setTimeout:(fn,delay)=>{timers.push({fn,delay});return timers.length;},clearTimeout:()=>{}
};
vm.createContext(context);vm.runInContext(block+'\nglobalThis.refresh=ensureSchedules;',context);
assert.equal(requests.length,0,'Unrelated pages must not fetch schedules');
assert.equal(timers.length,0,'Unrelated pages must not start polling');
context.location.pathname='/sesendoknew/rka';context.window.app.state.tbl='rka';context.refresh();
assert.equal(requests.length,1);assert.equal(timers.at(-1).delay,60000);
context.refresh();listeners.focus();assert.equal(requests.length,1,'Pending requests must not overlap');
requests[0].complete();context.document.hidden=true;now+=60000;context.refresh();assert.equal(requests.length,1,'Hidden tabs must stop requests');
context.document.hidden=false;context.refresh();assert.equal(requests.length,2,'Returning to active planning may refresh');
requests[1].complete();context.location.pathname='/sesendoknew/dashboard';context.refresh();assert.equal(requests.length,2,'Dashboard uses its own status request');
assert(!block.includes('setInterval('),'No repeating global scans');
console.log('PASS: No schedule polling on unrelated pages or hidden tabs; one request at a time; refresh interval is 60 seconds; no per-second DOM scan.');
