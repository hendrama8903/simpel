<?php

namespace App\Repositories;

use App\Models\School;
use App\Support\DBResult;
use Illuminate\Http\UploadedFile;

/**
 * Profil sekolah (satu baris). Dipakai kop rapor/kartu ujian, tanda tangan kepala sekolah,
 * serta KKTP & bobot nilai akhir rapor.
 */
class SchoolRepo
{
    public const Levels = ['SD' => 'SD (Sekolah Dasar)', 'MI' => 'MI (Madrasah Ibtidaiyah)'];

    private const Fields = ['Npsn' => 'npsn', 'SchoolName' => 'school_name', 'SchoolLevel' => 'school_level', 'Address' => 'address',
        'Village' => 'village', 'District' => 'district', 'City' => 'city', 'Province' => 'province', 'PostalCode' => 'postal_code',
        'Phone' => 'phone', 'Email' => 'email', 'Website' => 'website', 'PrincipalName' => 'principal_name',
        'PrincipalNip' => 'principal_nip', 'ReportPlace' => 'report_place'];

    public function get(): object
    {
        $school = School::query()->first();
        $data = ['SchoolId' => $school?->school_id, 'HasLogo' => $school?->logo_path !== null];
        foreach (self::Fields as $key => $column) {
            $data[$key] = $school?->{$column};
        }
        $data['SchoolLevel'] ??= 'SD';
        $data['Kktp'] = $school?->kktp ?? 70;
        $data['WeightSummative'] = $school?->weight_summative ?? 50;
        $data['WeightSts'] = $school?->weight_sts ?? 25;
        $data['WeightSas'] = $school?->weight_sas ?? 25;

        return (object) $data;
    }

    public function save(array $data, string $loginUser): DBResult
    {
        $fields = [];
        foreach (self::Fields as $key => $column) {
            $value = trim((string) ($data[$key] ?? ''));
            $fields[$column] = $value === '' ? null : mb_substr($value, 0, $column === 'address' ? 255 : 150);
        }
        $fields['kktp'] = (int) ($data['Kktp'] ?? 70);
        $fields['weight_summative'] = (int) ($data['WeightSummative'] ?? 0);
        $fields['weight_sts'] = (int) ($data['WeightSts'] ?? 0);
        $fields['weight_sas'] = (int) ($data['WeightSas'] ?? 0);

        $error = match (true) {
            $fields['school_name'] === null => 'Nama sekolah wajib diisi.',
            !isset(self::Levels[$fields['school_level'] ?? '']) => 'Jenjang sekolah tidak valid.',
            $fields['kktp'] < 0 || $fields['kktp'] > 100 => 'KKTP harus antara 0 dan 100.',
            min($fields['weight_summative'], $fields['weight_sts'], $fields['weight_sas']) < 0 => 'Bobot nilai tidak boleh negatif.',
            $fields['weight_summative'] + $fields['weight_sts'] + $fields['weight_sas'] !== 100 => 'Jumlah bobot sumatif, STS, dan SAS harus 100.',
            default => null,
        };
        if ($error !== null) {
            return new DBResult(false, $error);
        }

        $school = School::query()->first();
        if ($school === null) {
            $school = School::create($fields + ['created_by' => $loginUser]);
        } else {
            $school->update($fields + ['updated_by' => $loginUser]);
        }

        return new DBResult(true, 'Profil sekolah berhasil disimpan.', $school->school_id);
    }

    public function saveLogo(?UploadedFile $file, string $loginUser): DBResult
    {
        $school = School::query()->first();
        if ($school === null) {
            return new DBResult(false, 'Simpan profil sekolah terlebih dahulu.');
        }
        if ($error = self::validateImage($file)) {
            return new DBResult(false, $error);
        }

        $path = $file->storeAs('uploads/school', 'logo.' . strtolower($file->getClientOriginalExtension()), 'local');
        if ($school->logo_path !== null && $school->logo_path !== $path) {
            @unlink(storage_path('app/private/' . $school->logo_path));
        }
        $school->update(['logo_path' => $path, 'updated_by' => $loginUser]);

        return new DBResult(true, 'Logo berhasil diunggah.');
    }

    /** Path file logo di disk (untuk response gambar & PDF), null kalau belum ada. */
    public static function logoFile(): ?string
    {
        $path = School::query()->value('logo_path');

        return $path !== null && is_file(storage_path('app/private/' . $path)) ? storage_path('app/private/' . $path) : null;
    }

    public static function validateImage(?UploadedFile $file): ?string
    {
        return match (true) {
            $file === null || !$file->isValid() => 'Pilih file gambar.',
            !in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'], true) => 'Hanya gambar JPG atau PNG yang diperbolehkan.',
            $file->getSize() > 2 * 1024 * 1024 => 'Ukuran gambar maks. 2 MB.',
            @getimagesize($file->getRealPath()) === false => 'File bukan gambar yang valid.',
            default => null,
        };
    }
}
