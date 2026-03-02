/* This PHP class named KepegawaianController extends Controller and has a method index that sets the
'tbl' parameter based on the 'tbl' query parameter or defaults to 'asn'. */
<?php
require_once __DIR__ . '/../Core/Auth.php';

class KepegawaianController extends Controller
{
    private $allowedTables = [
        'asn',
        'sk_asn',
        'register_surat',
        'tata_naskah'
    ];

    public function index()
    {
        $tbl = $_GET['tbl'] ?? 'asn';

        return $this->view('kepegawaian/index', [
    'module' => 'kepegawaian',
    'tbl'    => $tbl
]);
    }
}
