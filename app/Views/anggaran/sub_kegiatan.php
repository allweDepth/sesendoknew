<?php

/*
|--------------------------------------------------------------------------
| SUB KEGIATAN VIEW
|--------------------------------------------------------------------------
| Digunakan oleh:
| renja
| renja_perubahan
| rka
| rka_perubahan
| dpa
| dppa
|--------------------------------------------------------------------------
*/

$tbl = isset($table) ? $table : '';

?>

<div id="crud-table-container">

       <!-- TOOLBAR -->
       <div class="ui right floated basic icon buttons" style="margin-top:10px;">

              <!-- ADD SUB KEGIATAN -->
              <button class="ui button"
                     data-ui="open-form"
                     data-container="flyout"
                     data-jns="add"
                     data-config="sub_kegiatan"
                     data-tbl="<?= $tbl ?>" data-req="group_sub_kegiatan">

                     <i class="plus icon"></i>
              </button>

              <!-- IMPORT -->
              <button class="ui button"
                     data-ui="open-form"
                     data-container="flyout"
                     data-jns="import"
                     data-tbl="<?= $tbl ?>" data-req="group_sub_kegiatan">

                     <i class="upload icon"></i>
              </button>

              <!-- EXPORT -->
              <button class="ui icon button"
                     data-action="export"
                     data-tbl="<?= $tbl ?>" data-req="group_sub_kegiatan">

                     <i class="alternate download icon"></i>
              </button>

       </div>

       <div class="ui hidden divider"></div>

       <!-- HEADER TABEL -->
       <h3 class="ui dividing header">
              <i class="left align icon"></i>
              Tabel Sub Kegiatan
       </h3>

       <!-- TABLE WRAPPER -->
       <div class="table-wrapper">

              <table class="ui very compact celled striped selectable table"
                     id="tableSubKegiatan"
                     data-table="<?= $tbl ?>"
                     data-req="group_sub_kegiatan"
                     data-group="kd_sub_keg"
                     data-key="kd_sub_keg">

                     <thead>
                            <tr>
                                   <th style="width:70px">Aksi</th>
                                   <th>Sub Kegiatan</th>
                                   <th style="width:120px">Status</th>
                                   <th style="width:140px">Status Rincian</th>
                                   <th style="width:140px">Sebelum Perubahan</th>
                                   <th style="width:140px">Pagu Validasi</th>
                                   <th style="width:160px">Total Rincian</th>
                                   <th style="width:120px">Realisasi %</th>
                            </tr>
                     </thead>

                     <tbody>

                            <tr>
                                   <td colspan="8" class="center aligned">
                                          <div class="ui info message">
                                                 Tidak ada data
                                          </div>
                                   </td>
                            </tr>

                     </tbody>

              </table>

       </div>

</div>