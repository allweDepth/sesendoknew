# Backup database

Backup lengkap yang disimpan di Git adalah `sesendoknew_db.sql.gz`.
Salinan SQL tanpa kompresi tetap tersedia secara lokal sebagai
`sesendoknew_db.sql`, tetapi tidak dilacak Git karena ukurannya melewati
batas berkas remote.

Untuk memperbarui keduanya dari MariaDB, jalankan dari root proyek:

```sh
bash scripts/export_database_portable.sh
```

Skrip memeriksa integritas gzip sebelum mengganti backup. Cadangan mencakup
struktur, data, view, trigger, routine, event, default Swakelola, serta
snapshot dokumen tersimpan.

Untuk mendapatkan SQL lengkap setelah clone, jalankan:

```sh
gzip -dc config/sesendoknew_db.sql.gz > config/sesendoknew_db.sql
```

File hasil ekstraksi adalah SQL lengkap untuk diimpor melalui alat database.
Ekstraksi tidak mengubah database yang berjalan.
