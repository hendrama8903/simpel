@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="sg-welcome">
        <h2>Selamat datang, {{ session('FullName') }}</h2>
        <p>{{ session('RoleName') }} &middot; Semester aktif: {{ $semesterLabel }} &middot; {{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
    </div>

    @if (!$hasSemester)
        <div class="dp-card sg-dash-card">
            <div class="sg-dash-title"><i class="fa fa-exclamation-triangle"></i> Belum ada semester aktif</div>
            <div class="sg-dash-empty">Buat tahun ajaran dan pilih semester aktif di menu Pengaturan Sekolah &rarr; Tahun Ajaran.</div>
        </div>
    @endif

    @if ($stats !== null)
        <div class="sg-tiles">
            <div class="dp-card sg-tile"><div class="sg-tile-ic"><i class="fa fa-child"></i></div><div><b>{{ $stats['students'] }}</b><span>Siswa aktif</span></div></div>
            <div class="dp-card sg-tile"><div class="sg-tile-ic"><i class="fa fa-user"></i></div><div><b>{{ $stats['teachers'] }}</b><span>Guru &amp; tendik aktif</span></div></div>
            <div class="dp-card sg-tile"><div class="sg-tile-ic"><i class="fa fa-users"></i></div><div><b>{{ $stats['classrooms'] }}</b><span>Rombel tahun ini</span></div></div>
            <div class="dp-card sg-tile"><div class="sg-tile-ic"><i class="fa fa-question"></i></div><div><b>{{ $stats['unplaced'] }}</b><span>Siswa aktif belum punya rombel</span></div></div>
        </div>
    @endif

    <div class="sg-dash-row">
        @if ($attendance !== [])
            <div class="dp-card sg-dash-card">
                <div class="sg-dash-title"><i class="fa fa-check-square-o"></i> Presensi Hari Ini</div>
                <ul class="sg-dash-list">
                    @foreach ($attendance as $a)
                        <li>
                            <a href="{{ url('/Attendance/Index') }}" class="sg-dash-class"><b>{{ $a->ClassroomName }}</b></a>
                            @if ($a->Members > 0)
                                {{-- Bar bertumpuk H/S/I/A terhadap jumlah anggota rombel (sisa = belum diisi). --}}
                                <span class="sg-att-bar" title="Hadir {{ $a->H }}, Sakit {{ $a->S }}, Izin {{ $a->I }}, Alpa {{ $a->A }}, belum diisi {{ $a->Members - $a->Recorded }}">
                                    @foreach (['H', 'S', 'I', 'A'] as $k)
                                        @if ($a->{$k} > 0)<span class="{{ $k }}" style="width: {{ round($a->{$k} / $a->Members * 100, 2) }}%"></span>@endif
                                    @endforeach
                                </span>
                            @endif
                            @if ($a->Recorded === 0)
                                <span class="sg-badge amber">Belum diisi ({{ $a->Members }} siswa)</span>
                            @else
                                <span>
                                    <span class="sg-att H">H {{ $a->H }}</span>
                                    <span class="sg-att S">S {{ $a->S }}</span>
                                    <span class="sg-att I">I {{ $a->I }}</span>
                                    <span class="sg-att A">A {{ $a->A }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($isTeacher)
            <div class="dp-card sg-dash-card">
                <div class="sg-dash-title"><i class="fa fa-clock-o"></i> Jadwal Mengajar Hari Ini</div>
                @if (count($todaySchedule) === 0)
                    <div class="sg-dash-empty">Tidak ada jadwal mengajar hari ini.</div>
                @else
                    <ul class="sg-dash-list">
                        @foreach ($todaySchedule as $s)
                            <li><span><b>{{ substr($s->start_time, 0, 5) }}-{{ substr($s->end_time, 0, 5) }}</b> &middot; {{ $s->subject->subject_name }}</span><span>{{ $s->classroom->classroom_name }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
</section>
@endsection

@section('scripts')
<style>
    .sg-dash-list li { align-items: center; }
    .sg-dash-class { min-width: 44px; }
    .sg-att-bar { flex: 1 1 auto; display: flex; height: 10px; border-radius: 5px; background: #EAECF0; overflow: hidden; }
    .sg-att-bar span { display: block; height: 100%; }
    .sg-att-bar .H { background: #1E9E63; } .sg-att-bar .S { background: #F2A93B; }
    .sg-att-bar .I { background: #2450D8; } .sg-att-bar .A { background: #C62828; }
</style>
@endsection
