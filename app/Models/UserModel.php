<?php
require_once __DIR__ . '/../Core/DB.php';

class UserModel
{

  private $db;

  public function __construct()
  {
    $this->db = DB::getInstance();
  }

  public function insertUser($data)
  {
    $db = DB::getInstance();

    // Ambil nama organisasi berdasarkan kd_opd
    $org = $db->query(
      "SELECT uraian FROM organisasi_neo WHERE kode = ?",
      [$data['kd_opd']]
    )->fetch(PDO::FETCH_ASSOC);

    $nama_org = $org['uraian'] ?? '';

    return $db->insert('user_sesendok_biila', [

      'username'      => $data['username'],
      'email'         => $data['email'],
      'nama'          => $data['nama'],
      'nip'           => $data['nip'],
      'password'      => $data['password'],
      'kd_opd'        => $data['kd_opd'],
      'nama_org'      => $nama_org, // ← WAJIB DIISI
      'kd_wilayah'    => $data['kd_wilayah'],
      'kontak_person' => $data['kontak_person'],
      'alamat'        => $data['alamat'],
      'tahun' => date('Y'),
      'type_user'     => 'user',
      'tgl_daftar'    => date('Y-m-d H:i:s'),
      'disable'       => 1
    ]);
  }
}
