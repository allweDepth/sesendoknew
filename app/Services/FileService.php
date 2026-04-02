<?php
// FILE: sesendoknew/app/Services/FileService.php

class FileService
{
  // ================= UPLOAD =================
  public static function upload($file, $module)
  {
    // cek file valid
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
      return null; // tidak ada file
    }

    // sanitize nama file
    $originalName = basename($file['name']); // nama asli
    $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName); // bersihkan karakter

    // generate nama unik
    $fileName = uniqid() . '_' . $originalName; // nama final

    // path berdasarkan module
    $basePath = __DIR__ . '/../../public/uploads/'; // root upload
    $targetPath = $basePath . $module . '/'; // folder module

    // buat folder jika belum ada
    if (!is_dir($targetPath)) {
      mkdir($targetPath, 0777, true); // recursive
    }

    // simpan file
    move_uploaded_file($file['tmp_name'], $targetPath . $fileName); // upload

    return $fileName; // return nama file
  }

  // ================= DELETE =================
  public static function delete($fileName, $module)
  {
    $path = __DIR__ . '/../../public/uploads/' . $module . '/' . $fileName; // path file

    if (file_exists($path)) { // cek file ada
      unlink($path); // hapus file
    }
  }

  // ================= PATH GETTER =================
  public static function getPath($fileName, $module)
  {
    return '/uploads/' . $module . '/' . $fileName; // untuk akses frontend
  }
}