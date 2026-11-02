{{--
    Rapor Kurikulum Merdeka (SD/MI) untuk dompdf. Satu siswa = (sampul + identitas bila $withCover) + halaman capaian.
    Parameter: $reports (ReportCardRepo::printData), $school, $classroom, $semester, $withCover.
--}}
@php
    use App\Support\Academic;
    $place = $school->report_place ?: $school->city;
    $reportDate = Academic::dateText($semester->report_date ?? now());
    $homeroom = $classroom->homeroomTeacher;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._style')
    <style>
        .ident td { padding: 2px 4px; vertical-align: top; }
        .section { font-weight: bold; margin: 12px 0 4px; }
        table.grid td { vertical-align: top; }
        .sign { width: 100%; margin-top: 18px; }
        .sign td { width: 33%; text-align: center; vertical-align: top; }
        .sign .space { height: 55px; }
        .cover { text-align: center; padding-top: 80px; }
        .cover h2 { font-size: 20px; margin: 0 0 6px; }
        .cover .box { border: 1px solid #333; padding: 8px; width: 60%; margin: 6px auto; font-size: 13px; font-weight: bold; }
        .cover img { width: 120px; height: 120px; margin: 30px 0; }
    </style>
</head>
<body>
@foreach ($reports as $i => $r)
    @php($s = $r->student)

    @if ($withCover)
        @php($logoFile = \App\Repositories\SchoolRepo::logoFile())
        <div class="cover">
            <h2>LAPORAN HASIL BELAJAR</h2>
            <div style="font-size: 13px;">(RAPOR)</div>
            @if ($logoFile)<img src="data:{{ mime_content_type($logoFile) }};base64,{{ base64_encode(file_get_contents($logoFile)) }}">@else<div style="height: 80px;"></div>@endif
            <div style="font-size: 14px; font-weight: bold;">{{ mb_strtoupper($school->school_name) }}</div>
            <div style="margin-top: 50px;">Nama Peserta Didik</div>
            <div class="box">{{ mb_strtoupper($s->student_name) }}</div>
            <div style="margin-top: 10px;">NIS / NISN</div>
            <div class="box">{{ $s->nis }} / {{ $s->nisn ?: '-' }}</div>
        </div>
        <div class="page-break"></div>

        @include('pdf._kop')
        <h1>IDENTITAS PESERTA DIDIK</h1>
        <table class="ident" style="width: 100%; font-size: 11px;">
            @foreach ([
                'Nama Peserta Didik' => $s->student_name,
                'NIS / NISN' => $s->nis . ' / ' . ($s->nisn ?: '-'),
                'Tempat, Tanggal Lahir' => trim(($s->birth_place ?? '') . ', ' . Academic::dateText($s->birth_date), ', '),
                'Jenis Kelamin' => $s->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                'Agama' => $s->religion,
                'Alamat Peserta Didik' => $s->address,
                'Nama Ayah' => $s->father_name,
                'Nama Ibu' => $s->mother_name,
                'Nama Wali' => $s->guardian_name,
                'No. HP Orang Tua/Wali' => $s->parent_phone,
                'Diterima di Sekolah Ini' => trim('Kelas ' . $s->entry_grade . ($s->entry_date ? ', ' . Academic::dateText($s->entry_date) : '')),
                'Sekolah Asal' => $s->previous_school,
            ] as $label => $value)
                <tr><td style="width: 200px;">{{ $loop->iteration }}. {{ $label }}</td><td style="width: 10px;">:</td><td>{{ $value ?: '-' }}</td></tr>
            @endforeach
        </table>
        <table class="sign">
            <tr>
                <td></td><td></td>
                <td>{{ $place }}, {{ $reportDate }}<br>Kepala Sekolah<div class="space"></div><b>{{ $school->principal_name ?: '......................' }}</b><br>NIP. {{ $school->principal_nip ?: '-' }}</td>
            </tr>
        </table>
        <div class="page-break"></div>
    @endif

    @include('pdf._kop')
    <h1>LAPORAN HASIL BELAJAR</h1>
    <table class="ident" style="width: 100%;">
        <tr>
            <td style="width: 110px;">Nama Peserta Didik</td><td style="width: 8px;">:</td><td><b>{{ $s->student_name }}</b></td>
            <td style="width: 90px;">Kelas</td><td style="width: 8px;">:</td><td style="width: 120px;">{{ $classroom->classroom_name }}</td>
        </tr>
        <tr>
            <td>NIS / NISN</td><td>:</td><td>{{ $s->nis }} / {{ $s->nisn ?: '-' }}</td>
            <td>Fase</td><td>:</td><td>{{ Academic::phase($classroom->grade_level) }}</td>
        </tr>
        <tr>
            <td>Sekolah</td><td>:</td><td>{{ $school->school_name }}</td>
            <td>Semester</td><td>:</td><td>{{ $semester->semester_no }} ({{ Academic::semesterName($semester->semester_no) }})</td>
        </tr>
        <tr>
            <td>Alamat</td><td>:</td><td>{{ $school->address ?: '-' }}</td>
            <td>Tahun Pelajaran</td><td>:</td><td>{{ $classroom->academicYear->year_name }}</td>
        </tr>
    </table>

    <table class="grid" style="margin-top: 8px;">
        <thead>
            <tr><th style="width: 22px;">No</th><th style="width: 150px;">Mata Pelajaran</th><th style="width: 48px;">Nilai Akhir</th><th>Capaian Kompetensi</th></tr>
        </thead>
        <tbody>
            @forelse ($r->subjects as $g)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $g->subject->subject_name }}</td>
                    <td class="center">{{ $g->final_score === null ? '-' : round($g->final_score) }}</td>
                    <td>
                        {{ $g->description_high }}
                        @if ($g->description_high && $g->description_low)<br>@endif
                        {{ $g->description_low }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="center muted">Belum ada nilai rapor.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($r->note?->cocurricular_note)
        <div class="section">Kokurikuler</div>
        <table class="grid"><tr><td>{{ $r->note->cocurricular_note }}</td></tr></table>
    @endif

    <div class="section">Ekstrakurikuler</div>
    <table class="grid">
        <tr><th style="width: 22px;">No</th><th style="width: 200px;">Kegiatan Ekstrakurikuler</th><th style="width: 90px;">Predikat</th><th>Keterangan</th></tr>
        @forelse ($r->extracurriculars as $e)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td>{{ $e->extracurricular->extracurricular_name }}</td>
                <td class="center">{{ $e->predicate }}</td>
                <td>{{ $e->description ?: 'Ananda ' . $s->student_name . ' mengikuti kegiatan ' . $e->extracurricular->extracurricular_name . ' dengan ' . mb_strtolower($e->predicate) . '.' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="center muted">-</td></tr>
        @endforelse
    </table>

    <table style="width: 100%; margin-top: 12px;">
        <tr>
            <td style="width: 42%; vertical-align: top;">
                <div class="section" style="margin-top: 0;">Ketidakhadiran</div>
                <table class="grid">
                    <tr><td>Sakit</td><td class="center" style="width: 70px;">{{ $r->note?->sick ?? 0 }} hari</td></tr>
                    <tr><td>Izin</td><td class="center">{{ $r->note?->permit ?? 0 }} hari</td></tr>
                    <tr><td>Tanpa Keterangan</td><td class="center">{{ $r->note?->absent ?? 0 }} hari</td></tr>
                </table>
            </td>
            <td style="width: 4%;"></td>
            <td style="vertical-align: top;">
                <div class="section" style="margin-top: 0;">Catatan Wali Kelas</div>
                <table class="grid"><tr><td style="height: 52px;">{{ $r->note?->homeroom_note }}</td></tr></table>
                @if ($r->decision)
                    <div class="section">Keputusan</div>
                    <table class="grid"><tr><td>Berdasarkan pencapaian seluruh kompetensi, peserta didik dinyatakan: <b>{{ $r->decision }}</b></td></tr></table>
                @endif
            </td>
        </tr>
    </table>

    <table class="sign">
        <tr>
            <td>Mengetahui,<br>Orang Tua/Wali<div class="space"></div>......................................</td>
            <td></td>
            <td>{{ $place }}, {{ $reportDate }}<br>Wali Kelas<div class="space"></div><b>{{ $homeroom?->teacher_name ?: '......................' }}</b><br>NIP. {{ $homeroom?->nip ?: '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Mengetahui,<br>Kepala Sekolah<div class="space"></div><b>{{ $school->principal_name ?: '......................' }}</b><br>NIP. {{ $school->principal_nip ?: '-' }}</td>
            <td></td>
        </tr>
    </table>

    @if (!$loop->last)<div class="page-break"></div>@endif
@endforeach
</body>
</html>
