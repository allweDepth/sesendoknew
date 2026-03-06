class AnggaranModule {

constructor(config)
{
    this.table = config.table;
    this.tahap = config.tahap;
}

async loadSubKegiatan()
{
    let res = await $.post('/dynamic',{
        table:'group_sub_kegiatan',
        action:'list',
        where:{ tahap:this.tahap }
    });

    this.renderSub(res.data);
}

renderSub(rows)
{
    let html='';

    rows.forEach(r=>{

        html+=`
        <tr data-sub="${r.kd_sub_keg}">
            <td>${r.kd_sub_keg}</td>
            <td>${r.nama_sub_keg}</td>
            <td>${r.total_anggaran}</td>
        </tr>
        `;
    });

    $('#tableSubKegiatan tbody').html(html);
}

async loadRekap(sub)
{
    let res = await $.post('/dynamic',{
        table:'group_rekap_akun',
        action:'list',
        where:{ kd_sub_keg:sub }
    });

    this.renderRekap(res.data);
}

renderRekap(rows)
{
    let html='';

    rows.forEach(r=>{

        html+=`
        <tr data-akun="${r.kd_akun}">
            <td>${r.kd_akun}</td>
            <td>${r.total_anggaran}</td>
        </tr>
        `;
    });

    $('#tableRekapAkun tbody').html(html);
}

async loadRincian(sub,akun)
{
    let res = await $.post('/dynamic',{
        table:this.table,
        action:'list',
        where:{
            kd_sub_keg:sub,
            kd_akun:akun
        }
    });

    this.renderRincian(res.data);
}

renderRincian(rows)
{
    let html='';

    rows.forEach(r=>{

        html+=`
        <tr>
            <td>${r.uraian}</td>
            <td>${r.volume}</td>
            <td>${r.harga_satuan}</td>
            <td>${r.jumlah}</td>
        </tr>
        `;
    });

    $('#tableRincian tbody').html(html);
}

}