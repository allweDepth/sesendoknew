<?php

spl_autoload_register(function ($class) { // //

  $prefix = 'App\\Services\\DynamicTable\\'; // namespace target //

  if (str_starts_with($class, $prefix)) { // cek namespace //

    $relative = str_replace($prefix, '', $class); // hapus prefix //

    $file = __DIR__ . '/' . $relative . '.php'; // path file //

    if (file_exists($file)) { // jika ada file //
      require_once $file; // load otomatis //
    }
  }
});