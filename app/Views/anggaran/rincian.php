<?php

/*
|--------------------------------------------------------------------------
| RINCIAN BELANJA
|--------------------------------------------------------------------------
*/

$tbl = isset($table) ? $table : '';
$sub = isset($sub) ? $sub : '';

?>

<div class="ui segment">

       <button class="ui button"
              id="btnBackSubKegiatan">

              <i class="arrow left icon"></i>

              Kembali

       </button>

</div>



<table class="ui celled selectable compact table"
       id="tableSubKegiatan"

       data-table="<?= $tbl ?>"
       data-group="kd_sub_keg"
       data-key="kd_sub_keg"

       data-sum="jumlah"
       data-count="id"
       data-status="setujui">

       <thead>

              <tr>

                     <th style="width:80px">Aksi</th>

                     <th>Komponen</th>

                     <th style="width:120px">Volume</th>

                     <th style="width:120px">Satuan</th>

                     <th style="width:160px">Harga Satuan</th>

                     <th style="width:180px">Jumlah</th>

              </tr>

       </thead>

       <tbody>

              <tr class="empty-row">

                     <td colspan="6">

                            <div class="ui warning message">

                                   <i class="info circle icon"></i>

                                   Belum ada rincian belanja.

                            </div>

                     </td>

              </tr>

       </tbody>

</table>