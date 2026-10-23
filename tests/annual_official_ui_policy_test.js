const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../public/assets/js/app-init.js','utf8');
const policy=source.slice(source.indexOf('\twindow.moduleMutationPolicy ='),source.indexOf('\n    // All OPD mutation controls'));
const context={window:{location:{pathname:'/sesendoknew/kepegawaian'},APP_BASE_PATH:'/sesendoknew',app:{user:{kd_wilayah:'76.01',kd_opd:'OPD',tahun:2026}}}};
vm.createContext(context);vm.runInContext(policy,context);
for(const role of ['admin_opd','kepala_opd','pa_kpa','admin_wilayah','super_admin','tapd','ppk','pptk','viewer']){
 context.window.app.user.type_user=role;
 const allowed=['admin_opd','kepala_opd','pa_kpa','admin_wilayah'].includes(role);
 const p=context.window.moduleMutationPolicy('pejabat_tahunan');
 for(const action of ['add','edit','delete'])assert.equal(p[action],allowed,role+' '+action);
 assert.equal(p.import,false);
}
context.window.app.user.type_user='admin_wilayah';context.window.app.user.kd_opd='0';
assert.equal(context.window.moduleMutationPolicy('pejabat_tahunan').add,false);
context.window.app.user.kd_opd='OPD';context.window.app.user.type_user='admin_opd';
assert.equal(context.window.moduleMutationPolicy('penugasan_subkegiatan').add,false,'Assignment authority stays unchanged');
console.log('PASS: Annual official buttons match server role/OPD rules; subactivity assignments retain their permissions');
