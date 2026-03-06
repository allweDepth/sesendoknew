<?php $tbl = isset($table) ? $table : ''; ?>

<div id="crud-table-container">

       <!-- BACK -->
       <button class="ui basic button" id="btnBackAkun">
              <i class="arrow left icon"></i>
              Kembali
       </button>

       <div class="ui hidden divider"></div>

       <h3 class="ui dividing header">
              Rincian Belanja
       </h3>

       <div class="table-wrapper">

              <table class="ui very compact celled striped selectable table"
                     id="tableRincian"

                     data-table="<?= $tbl ?>">

                     <thead>
                            <tr>
                                   <th style="width:70px">Aksi</th>
                                   <th>Komponen</th>
                                   <th>Volume</th>
                                   <th>Satuan</th>
                                   <th>Harga</th>
                                   <th>Total</th>
                            </tr>
                     </thead>

                     <tbody>

                            <tr>
                                   <td colspan="6" class="center aligned">
                                          <div class="ui info message">
                                                 Tidak ada data
                                          </div>
                                   </td>
                            </tr>

                     </tbody>

              </table>

       </div>

</div>