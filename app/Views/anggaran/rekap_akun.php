<?php $tbl = isset($table) ? $table : ''; ?>

<div id="crud-table-container">

       <!-- BUTTON BACK -->
       <button class="ui basic button" id="btnBackSub">
              <i class="arrow left icon"></i>
              Kembali
       </button>

       <div class="ui hidden divider"></div>

       <h3 class="ui dividing header">
              Rekap Akun Belanja
       </h3>

       <div class="table-wrapper">

              <table class="ui very compact celled striped selectable table"
                     id="tableRekapAkun"

                     data-table="<?= $tbl ?>"
                     data-group="kd_akun"
                     data-key="kd_akun">

                     <thead>
                            <tr>
                                   <th style="width:60px">Aksi</th>
                                   <th>Akun Belanja</th>
                                   <th>Total</th>
                            </tr>
                     </thead>

                     <tbody>

                            <tr>
                                   <td colspan="3" class="center aligned">
                                          <div class="ui info message">
                                                 Tidak ada data
                                          </div>
                                   </td>
                            </tr>

                     </tbody>

              </table>

       </div>

</div>