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
    return $this->db->insert('user_sesendok_biila', [
      'username'      => $data['username'],
      'email'         => $data['email'],
      'nama_lengkap'  => $data['nama_lengkap'],
      'nip'           => $data['nip'],
      'kontak_person' => $data['kontak_person'],
      'alamat'        => $data['alamat'],
      'password'      => $data['password'],
      'wilayah'       => $data['wilayah'],
      'organisasi'    => $data['organisasi'],
    ]);
  }
}
