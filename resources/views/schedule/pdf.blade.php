<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._style')
    <style>
        table.grid { table-layout: fixed; }
        table.grid td { height: 34px; text-align: center; }
        .subj { font-weight: bold; }
        .tchr { font-size: 8px; color: #444; }
    </style>
</head>
<body>
    @include('pdf._kop')
    <h1>JADWAL PELAJARAN KELAS {{ $classroom->classroom_name }}<br><span style="font-size: 10.5px; font-weight: normal;">Semester {{ $semesterLabel }} &middot; Wali Kelas: {{ $classroom->homeroomTeacher?->teacher_name ?? '-' }}</span></h1>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 40px;">Jam</th>
                <th style="width: 75px;">Waktu</th>
                @foreach ($days as $name)
                    <th>{{ $name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($periods as $p)
                @continue(!$p->StartTime && collect($days)->keys()->every(fn ($d) => !$p->{"D{$d}"}))
                <tr>
                    <td>{{ $p->PeriodNo }}</td>
                    <td>{{ $p->StartTime }} - {{ $p->EndTime }}</td>
                    @foreach ($days as $day => $name)
                        @php($subjectId = $p->{"D{$day}"})
                        <td>
                            @if ($subjectId)
                                <div class="subj">{{ $subjects[$subjectId] ?? '' }}</div>
                                <div class="tchr">{{ $teachers[$subjectId] ?? '' }}</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="muted">Dicetak {{ \App\Support\Academic::dateText(now()) }}</p>
</body>
</html>
