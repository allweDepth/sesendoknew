<?php

class FileService
{
  private const EXTENSIONS = [
    'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
    'application/pdf' => 'pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
  ];

  private static function directory(string $module): string
  {
    if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $module)) throw new InvalidArgumentException('Modul file tidak valid.');
    return __DIR__ . '/../../public/uploads/' . $module . '/';
  }

  public static function upload($file, $module)
  {
    $target = self::directory($module);
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
      throw new InvalidArgumentException('Unggahan tidak valid.');
    }
    if (filesize($file['tmp_name']) > 15 * 1024 * 1024) throw new InvalidArgumentException('File maksimal 15 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = self::EXTENSIONS[$mime] ?? null;
    if (!$ext) throw new InvalidArgumentException('Format file tidak diizinkan.');
    if (str_starts_with($mime, 'image/') && !getimagesize($file['tmp_name'])) throw new InvalidArgumentException('Gambar tidak valid.');
    if (!is_dir($target) && !mkdir($target, 0750, true)) throw new RuntimeException('Penyimpanan belum tersedia.');
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target . $name)) throw new RuntimeException('File belum dapat disimpan.');
    return $name;
  }

  public static function delete($fileName, $module)
  {
    $root = realpath(self::directory($module));
    if (!is_string($fileName) || $fileName !== basename($fileName)) throw new InvalidArgumentException('File tidak valid.');
    $path = $root ? realpath($root . '/' . $fileName) : false;
    if ($path && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path)) unlink($path);
  }

  public static function getPath($fileName, $module)
  {
    self::directory($module);
    if (!is_string($fileName) || $fileName !== basename($fileName)) throw new InvalidArgumentException('File tidak valid.');
    return '/uploads/' . rawurlencode($module) . '/' . rawurlencode($fileName);
  }
}
