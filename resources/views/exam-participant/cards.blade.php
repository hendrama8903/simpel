{{--
    Kartu peserta ujian untuk dompdf: 6 kartu per halaman A4 (2 kolom x 3 baris).
    Parameter: $exam, $participants (ExamParticipantRepo::search), $school.
--}}
@php
    use App\Repositories\StudentRepo;
    use App\Support\Academic;
    $logoFile = \App\Repositories\SchoolRepo::logoFile();
    $logoSrc = $logoFile === null ? null : 'data:' . mime_content_type($logoFile) . ';base64,' . base64_encode(file_get_contents($logoFile));
    $place = $school->report_place ?: $school->city;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18px 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        table.page { width: 100%; border-collapse: separate; border-spacing: 8px; }
        td.cell { width: 50%; vertical-align: top; }
        .card { border: 1.4px solid #1C3FA6; border-radius: 6px; height: 330px; }
        .card-head { background: #E8EEF9; border-bottom: 1.4px solid #1C3FA6; padding: 5px 6px; }
        .card-head table { width: 100%; }
        .card-head img { width: 34px; height: 34px; }
        .card-title { text-align: center; line-height: 1.3; }
        .card-title b { font-size: 10.5px; }
        .card-body { padding: 8px; }
        .photo { width: 76px; height: 101px; border: 1px solid #999; text-align: center; vertical-align: middle; color: #888; }
        .photo img { width: 76px; height: 101px; }
        .info td { padding: 2px 3px; vertical-align: top; font-size: 9.5px; }
        .no { font-size: 13px; font-weight: bold; letter-spacing: .5px; }
        .note { font-size: 8px; color: #333; padding: 0 8px; height: 26px; }
        .sign { text-align: center; width: 150px; float: right; margin-right: 8px; font-size: 8.5px; }
        .sign .space { height: 34px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
@foreach (array_chunk($participants, 6) as $page)
    <table class="page">
        @foreach (array_chunk($page, 2) as $row)
            <tr>
                @foreach ($row as $p)
                    @php($photo = StudentRepo::photoFile($p->PhotoPath))
                    <td class="cell">
                        <div class="card">
                            <div class="card-head">
                                <table>
                                    <tr>
                                        <td style="width: 38px;">@if ($logoSrc)<img src="{{ $logoSrc }}">@endif</td>
                                        <td class="card-title">
                                            <b>KARTU PESERTA</b><br>
                                            {{ mb_strtoupper($exam->exam_name) }}<br>
                                            {{ mb_strtoupper($school->school_name) }} &middot; {{ $exam->semester->academicYear->year_name }}
                                        </td>
                                        <td style="width: 38px;"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="card-body">
                                <table style="width: 100%;">
                                    <tr>
                                        <td class="photo">
                                            @if ($photo)<img src="data:{{ mime_content_type($photo) }};base64,{{ base64_encode(file_get_contents($photo)) }}">@else Foto<br>3 x 4 @endif
                                        </td>
                                        <td style="vertical-align: top; padding-left: 6px;">
                                            <table class="info" style="width: 100%;">
                                                <tr><td colspan="3"><span class="no">{{ $p->ParticipantNo }}</span></td></tr>
                                                <tr><td style="width: 46px;">Nama</td><td style="width: 6px;">:</td><td><b>{{ $p->StudentName }}</b></td></tr>
                                                <tr><td>NIS</td><td>:</td><td>{{ $p->Nis }}</td></tr>
                                                <tr><td>NISN</td><td>:</td><td>{{ $p->Nisn ?: '-' }}</td></tr>
                                                <tr><td>Kelas</td><td>:</td><td>{{ $p->ClassroomName }}</td></tr>
                                                <tr><td>Ruang</td><td>:</td><td>{{ $p->RoomName ?: '-' }}{{ $p->SeatNo ? ' / Kursi ' . $p->SeatNo : '' }}</td></tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="note">{{ $exam->card_note }}</div>
                            <div class="sign">
                                {{ $place }}, {{ Academic::dateText($exam->start_date) }}<br>Kepala Sekolah
                                <div class="space"></div>
                                <b>{{ $school->principal_name ?: '....................' }}</b><br>NIP. {{ $school->principal_nip ?: '-' }}
                            </div>
                        </div>
                    </td>
                @endforeach
                @if (count($row) === 1)<td class="cell"></td>@endif
            </tr>
        @endforeach
    </table>
    @if (!$loop->last)<div class="page-break"></div>@endif
@endforeach
</body>
</html>
