{{-- Daftar hadir peserta ujian, satu halaman per ruang. Parameter: $exam, $groups (ruang => peserta), $school. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._style')
    <style>
        table.grid td { height: 22px; }
        .ttd-l { width: 90px; }
    </style>
</head>
<body>
@foreach ($groups as $room => $list)
    @include('pdf._kop')
    <h1>DAFTAR HADIR PESERTA {{ mb_strtoupper($exam->exam_name) }}<br>
        <span style="font-size: 10.5px; font-weight: normal;">Ruang: {{ $room }} &middot; Tahun Ajaran {{ $exam->semester->academicYear->year_name }}</span></h1>
    <table style="width: 100%; margin-bottom: 6px;">
        <tr><td>Hari/Tanggal: ..............................................</td><td>Mata Pelajaran: ..............................................</td></tr>
    </table>
    <table class="grid">
        <thead>
            <tr><th style="width: 24px;">No</th><th style="width: 110px;">No Peserta</th><th>Nama</th><th style="width: 50px;">Kelas</th><th style="width: 40px;">Kursi</th><th colspan="2">Tanda Tangan</th></tr>
        </thead>
        <tbody>
            @foreach ($list->values() as $i => $p)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $p->ParticipantNo }}</td>
                    <td>{{ $p->StudentName }}</td>
                    <td class="center">{{ $p->ClassroomName }}</td>
                    <td class="center">{{ $p->SeatNo }}</td>
                    <td class="ttd-l">{{ $i % 2 === 0 ? ($i + 1) . '.' : '' }}</td>
                    <td class="ttd-l">{{ $i % 2 === 1 ? ($i + 1) . '.' : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table style="width: 100%; margin-top: 16px;">
        <tr>
            <td style="width: 50%;">Jumlah peserta: {{ $list->count() }}<br>Hadir: ........ &nbsp; Tidak hadir: ........</td>
            <td style="text-align: center;">Pengawas<div style="height: 50px;"></div>(..........................................)</td>
        </tr>
    </table>
    @if (!$loop->last)<div class="page-break"></div>@endif
@endforeach
</body>
</html>
