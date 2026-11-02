# SIMPEL — Sistem Informasi Sekolah (SD/MI)

Aplikasi administrasi sekolah dasar berbasis **Kurikulum Merdeka**: data guru & siswa, rombel, jadwal, presensi,
penilaian, e-rapor, dan kartu ujian. Dibangun dari template [TemplateAdminHMA](https://github.com/hendrama8903/TemplateAdminHMA)
(Laravel 12, jQuery + DevExtreme DataGrid, MySQL) dengan pola yang sama seperti proyek APRS.

## Setup

```bash
composer install
cp .env.example .env          # sesuaikan DB_* dan SIMPEL_* (lihat config/simpel.php)
php artisan key:generate
# buat database MySQL sesuai DB_DATABASE (default: simpel), lalu:
php artisan migrate --seed
php artisan serve
```

Login awal: **admin / Admin@123** (wajib diganti setelah login pertama).

### Data contoh

```bash
php artisan db:seed --class=DemoSeeder
```

Membuat profil sekolah, TA 2026/2027 (Ganjil aktif), rombel 1A–6A @20 siswa, jadwal tanpa bentrok, TP Matematika &
Bahasa Indonesia, dan ujian SAS2627. Akun guru (password `Guru@12345`): `kepsek`, `guru1`..`guru6` (wali kelas 1A..6A),
`guru.pai`, `guru.pjok`. **Jangan dijalankan di production.**

## Urutan pakai di awal tahun ajaran

1. **Pengaturan Sekolah → Profil Sekolah**: identitas, kepala sekolah, logo, KKTP & bobot nilai rapor.
2. **Tahun Ajaran**: buat tahun ajaran + tanggal semester, pilih semester aktif.
3. **Data Master**: mapel (default Kurikulum Merdeka SD sudah ada), ruangan, ekstrakurikuler, **Rombel** (atau *Salin dari Tahun Lain*).
4. **Kepegawaian → Guru & Tendik**: input/upload Excel, lalu *Buat Akun Login*. **Tugas Mengajar**: guru mapel per rombel.
5. **Kesiswaan → Data Siswa**: input/upload Excel (kolom ROMBEL langsung memasukkan ke rombel). **Anggota Rombel** untuk
   memasukkan siswa / kenaikan kelas (ambil calon anggota dari rombel tahun lalu).
6. **Akademik**: jadwal, presensi harian, TP, input nilai → *Hitung* di Nilai Rapor → data Wali Kelas → Cetak Rapor.
7. **Ujian**: buat kegiatan ujian + jadwal → Peserta & Kartu Ujian (generate peserta, atur ruang, cetak kartu & daftar hadir).
8. Akhir tahun: **Mutasi & Kelulusan → Luluskan Rombel** untuk kelas 6.

## Role & hak akses

| Role | Akses |
|---|---|
| ADMIN | Semua menu termasuk Administrasi (User, Role & Akses, Menu Master, Audit Trail) |
| OPERATOR | Semua menu sekolah, semua rombel |
| KEPSEK | Melihat semua menu sekolah & rombel, cetak rapor & kartu ujian (tanpa mengubah data) |
| GURU_KELAS | Data siswa (lihat), jadwal, presensi, TP, input nilai, nilai rapor, wali kelas & cetak rapor — **hanya rombel yang ia walikan/ajar** |
| GURU_MAPEL | Jadwal, TP, input nilai & nilai rapor — **hanya mapel yang ditugaskan kepadanya** |

Pembatasan rombel memakai permission **`<MENU>-ALL` ("Semua Rombel")**: tanpa permission itu, user hanya melihat rombel
milik guru yang terhubung ke akunnya (wali kelas atau Tugas Mengajar). Lihat `app/Support/Academic.php`.
Hak akses diatur di *Administrasi → Role & Akses*; user perlu login ulang setelah aksesnya diubah.

## Aturan penilaian (Kurikulum Merdeka)

- **Input Nilai**: satu kolom = satu penilaian — *Formatif* (wajib terkait TP), *Sumatif Lingkup Materi* (boleh terkait TP), *STS*, *SAS*.
- **Nilai akhir** = rata-rata berbobot komponen yang ada nilainya: rata-rata sumatif lingkup materi, STS, SAS
  (bobot di Profil Sekolah, default 50/25/25).
- **Deskripsi capaian** dari nilai per TP dibanding KKTP: maks. 2 TP tertinggi yang tuntas → "menunjukkan penguasaan yang baik
  dalam …"; maks. 2 TP terendah di bawah KKTP → "perlu bantuan dalam …".
- Nilai/deskripsi boleh diubah manual di Nilai Rapor; baris manual tidak ditimpa saat *Hitung* kecuali dicentang *Timpa*.

## Struktur kode

Pola template: Route PascalCase `/{Menu}/{Action}` (sama dengan `menus.menu_url`) → Controller → Repository (validasi &
aturan bisnis, mengembalikan `DBResult`) → Model.

| Bagian | File utama |
|---|---|
| Konteks akademik & pembatasan guru | `app/Support/Academic.php` |
| Import / template Excel | `app/Support/ExcelSheet.php`, `app/Http/Controllers/Concerns/HandlesExcelUpload.php`, `resources/views/partials/excel-upload.blade.php` |
| Lembar kerja editable (presensi, nilai, dll.) | `HMA.sheetGrid` & `HMA.guardFilter` di `public/js/hma.js` |
| Nilai rapor & deskripsi | `app/Repositories/ReportGradeRepo.php` |
| PDF (jadwal, rapor, kartu ujian, daftar hadir) | `resources/views/pdf/`, `schedule/pdf`, `report-card/pdf`, `exam-participant/cards`, `exam-participant/attendance` |
| Kolom tanggal | `app/Casts/DateOnly.php` (disimpan `Y-m-d`) |
| Data awal & contoh | `database/seeders/DatabaseSeeder.php`, `database/seeders/DemoSeeder.php` |

Foto siswa & logo disimpan di `storage/app/private/uploads` dan disajikan lewat controller (tidak publik).

## Test

```bash
php artisan test
```

Feature test (`tests/Feature/SchoolFlowTest.php`) memakai SQLite in-memory + DemoSeeder: semua menu terbuka, hitung nilai rapor
& deskripsi, pembatasan akses guru, presensi, anggota rombel, nomor peserta & ruang ujian.
