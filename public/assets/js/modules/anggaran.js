/*
|--------------------------------------------------------------------------
| MODULE ANGGARAN
|--------------------------------------------------------------------------
| Mengendalikan tampilan:
|
| sub kegiatan
| rekap akun
| rincian
|--------------------------------------------------------------------------
*/

class AnggaranModule {

constructor(container){

this.container = container
this.table = container.dataset.table

this.init()

}

init(){

this.loadSubKegiatan()

}



async loadSubKegiatan(){

let res = await $.post('/anggaran/sub_kegiatan',{
table:this.table
})

let tbody = $("#tableSubKegiatan tbody")

tbody.empty()

res.forEach(r=>{

tbody.append(`
<tr data-sub="${r.kd_sub_keg}">
<td>
<button class="ui mini button btn-sub">Detail</button>
</td>
<td>${r.kd_sub_keg}</td>
<td>${r.total}</td>
</tr>
`)

})

}



}