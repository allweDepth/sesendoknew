<?php

class AnggaranCopyController
{

    public function copy()
    {

        $from=$_POST['from'];
        $to=$_POST['to'];
        $tahun=$_POST['tahun'];

        AnggaranCopyService::copy($from,$to,$tahun);

        echo json_encode([
            'success'=>true
        ]);

    }

}