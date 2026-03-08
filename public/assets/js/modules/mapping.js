class MappingModule extends BaseCrudModule {

    constructor(){

        super({

            moduleName : "mapping",   // alias config

            menuItems : [

                { label:"SSH", tbl:"mapping", tipe:"ssh" },

                { label:"SBU", tbl:"mapping", tipe:"sbu" },

                { label:"ASB", tbl:"mapping", tipe:"asb" },

                { label:"HSPK", tbl:"mapping", tipe:"hspk" }

            ],

            useMenu : true

        });

    }

}