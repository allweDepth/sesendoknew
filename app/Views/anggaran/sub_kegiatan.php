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

<div class="ui segment">

       <div class="ui grid">

              <div class="eight wide column">

                     <div class="ui small basic label">
                            <i class="search icon"></i>
                            Gunakan kolom pencarian di navbar
                     </div>

              </div>

              <div class="eight wide column right aligned">

                     <div class="ui right floated basic icon buttons">

                            <!-- ADD SUB KEGIATAN -->

                            <button class="ui button"
                                   data-ui="open-form"
                                   data-container="flyout"
                                   data-jns="add"
                                   data-tbl="<?= $tbl ?>">

                                   <i class="plus icon"></i>

                            </button>


                            <!-- IMPORT STRUKTUR -->

                            <button class="ui button"
                                   data-ui="open-form"
                                   data-container="flyout"
                                   data-jns="import"
                                   data-tbl="<?= $tbl ?>">

                                   <i class="import icon"></i>

                            </button>


                            <!-- EXPORT -->

                            <button class="ui icon button"
                                   data-action="export"
                                   data-tbl="<?= $tbl ?>">

                                   <i class="alternate download icon"></i>

                            </button>

                     </div>

              </div>

       </div>

</div>



<table class="ui celled selectable compact table"
       id="tableSubKegiatan"

       data-table="<?= $tbl ?>"
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

              <tr class="empty-row">

                     <td colspan="8">

                            <div class="ui warning message">

                                   <i class="info circle icon"></i>

                                   Belum ada data sub kegiatan.

                            </div>

                     </td>

              </tr>

       </tbody>

</table>