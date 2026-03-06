$(function(){

let container = $('[data-module="anggaran"]');

let table = container.data('table');
let tahap = container.data('tahap');

let app = new AnggaranModule({
    table:table,
    tahap:tahap
});

app.loadSubKegiatan();

$('#tableSubKegiatan').on('click','tr',function(){

let sub=$(this).data('sub');

app.loadRekap(sub);

});

$('#tableRekapAkun').on('click','tr',function(){

let akun=$(this).data('akun');
let sub=$('#tableSubKegiatan tr.active').data('sub');

app.loadRincian(sub,akun);

});

});