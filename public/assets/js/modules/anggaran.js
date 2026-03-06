/*
|--------------------------------------------------------------------------
| MODULE : ANGGARAN
|--------------------------------------------------------------------------
| Modul ini hanya mengatur workflow tampilan:
|
| 1. Sub Kegiatan
| 2. Rincian Belanja
|
| Form add/edit ditangani oleh:
| form-engine.js
|
| Flyout ditangani oleh:
| flyout-controller.js
|
| Tabel ditangani oleh:
| table-manager.js
|--------------------------------------------------------------------------
*/

class AnggaranModule
{

    constructor(container)
    {

        /*
        |--------------------------------------------------------------------------
        | Container modul
        |--------------------------------------------------------------------------
        */

        this.container = container


        /*
        |--------------------------------------------------------------------------
        | Tabel aktif
        |--------------------------------------------------------------------------
        */

        this.table = container.dataset.table


        /*
        |--------------------------------------------------------------------------
        | Tahap dokumen
        |--------------------------------------------------------------------------
        */

        this.tahap = container.dataset.tahap


        this.init()

    }



    /*
    |--------------------------------------------------------------------------
    | INIT MODULE
    |--------------------------------------------------------------------------
    */

    init()
    {

        this.bindEvents()

        this.initTable()

    }



    /*
    |--------------------------------------------------------------------------
    | INIT TABLE
    |--------------------------------------------------------------------------
    | TableManager akan membaca atribut data-table
    |--------------------------------------------------------------------------
    */

    initTable()
    {

        const table = this.container.querySelector("#tableSubKegiatan")

        if(!table) return

        if(typeof TableManager !== "undefined")
        {

            new TableManager(table)

        }

    }



    /*
    |--------------------------------------------------------------------------
    | EVENT BINDING
    |--------------------------------------------------------------------------
    */

    bindEvents()
    {

        const self = this



        /*
        |--------------------------------------------------------------------------
        | KLIK SUB KEGIATAN
        |--------------------------------------------------------------------------
        | Membuka halaman rincian
        |--------------------------------------------------------------------------
        */

        $(document).on("click","#tableSubKegiatan tbody tr",function(e){

            /*
            |--------------------------------------------------------------------------
            | Hindari klik tombol aksi
            |--------------------------------------------------------------------------
            */

            if($(e.target).closest("button").length) return

            let sub = $(this).data("sub")

            if(!sub) return

            self.openRincian(sub)

        })



        /*
        |--------------------------------------------------------------------------
        | KEMBALI KE SUB KEGIATAN
        |--------------------------------------------------------------------------
        */

        $(document).on("click","#btnBackSubKegiatan",function(){

            self.backToSub()

        })



    }



    /*
    |--------------------------------------------------------------------------
    | BUKA HALAMAN RINCIAN BELANJA
    |--------------------------------------------------------------------------
    */

    openRincian(sub)
    {

        const self = this

        $.get("/anggaran/rincian",{

            table : this.table,
            sub   : sub,
            tahap : this.tahap

        },function(html){

            $("#anggaranContainer").html(html)

        })

    }



    /*
    |--------------------------------------------------------------------------
    | KEMBALI KE HALAMAN SUB KEGIATAN
    |--------------------------------------------------------------------------
    */

    backToSub()
    {

        const self = this
self.initTable()
        $.get("/anggaran/sub_kegiatan",{

            table : this.table,
            tahap : this.tahap

        },function(html){

            $("#anggaranContainer").html(html)

            self.initTable()

        })

    }



}