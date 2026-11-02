{{--
    Anggota rombel: kiri = calon anggota (siswa aktif yang belum punya rombel di tahun ajaran tsb,
    opsional hanya dari satu rombel sumber, mis. rombel tahun lalu untuk kenaikan kelas), kanan = anggota rombel.
--}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="sg-filterbar">
            <div class="sg-field">
                <label for="filterYear">Tahun Ajaran</label>
                <select class="form-control" id="filterYear">
                    @foreach ($years as $y)
                        <option value="{{ $y->academic_year_id }}" @selected($y->academic_year_id === $activeYearId)>{{ $y->year_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sg-field">
                <label for="filterClassroom">Rombel</label>
                <select class="form-control" id="filterClassroom"></select>
            </div>
            <div class="sg-field sg-field-grow">
                <label for="filterSource">Ambil Calon Anggota dari</label>
                <select class="form-control" id="filterSource"></select>
            </div>
        </div>
        <div class="sg-info">Untuk kenaikan kelas: pilih rombel tahun ajaran baru, lalu ambil calon anggota dari rombel tahun sebelumnya. Siswa tinggal kelas dimasukkan ke rombel tingkat yang sama.</div>
    </div>

    <div class="cm-panels">
        <div class="dp-card">
            <div class="box-header dp-toolbar">
                <div class="dp-toolbar-left">
                    <b class="cm-title">Calon Anggota</b>
                    @if ($edit)
                        <button type="button" class="dp-btn dp-btn-primary" id="btn_add" disabled>Masukkan <i class="fa fa-arrow-right"></i></button>
                    @endif
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
                </div>
            </div>
            <div class="box-body"><div id="candidateGrid"></div></div>
        </div>

        <div class="dp-card">
            <div class="box-header dp-toolbar">
                <div class="dp-toolbar-left">
                    <b class="cm-title">Anggota <span id="memberTitle"></span></b>
                    @if ($edit)
                        <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_remove" disabled><i class="fa fa-arrow-left"></i> Keluarkan</button>
                    @endif
                </div>
            </div>
            <div class="box-body"><div id="memberGrid"></div></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<style>
    .cm-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; }
    .cm-title { font-size: 13.5px; margin-right: 6px; }
    @media (max-width: 992px) { .cm-panels { grid-template-columns: 1fr; } }
</style>
<script type="text/javascript">
    var gClassrooms = @json($classrooms);
    var gCandidateGrid, gMemberGrid;

    function classroomId() { return $("#filterClassroom").val() || ""; }

    function fillClassrooms() {
        var yearId = parseInt($("#filterYear").val(), 10);
        var $c = $("#filterClassroom").empty();
        var $s = $("#filterSource").empty().append(new Option("Siswa aktif yang belum punya rombel", ""));
        $.each(gClassrooms, function (_, c) {
            if (c.AcademicYearId === yearId) $c.append(new Option(c.ClassroomName, c.ClassroomId));
            else $s.append(new Option("Rombel " + c.ClassroomName + " (" + c.YearName + ")", c.ClassroomId));
        });
        if (!$c.children().length) $c.append(new Option("- belum ada rombel -", ""));
    }

    function reload() {
        var c = $.grep(gClassrooms, function (x) { return String(x.ClassroomId) === classroomId(); })[0];
        $("#memberTitle").text(c ? c.ClassroomName : "");
        gCandidateGrid.clearSelection();
        gMemberGrid.clearSelection();
        gCandidateGrid.refresh();
        gMemberGrid.refresh();
    }

    function studentColumns() {
        return [
            HMA.col.rowNumber(),
            { dataField: "Nis", caption: "NIS" },
            { dataField: "StudentName", caption: "Nama" },
            { dataField: "Gender", caption: "JK", alignment: "center", width: 60 }
        ];
    }

    $(function () {
        fillClassrooms();

        gCandidateGrid = HMA.createGrid("#candidateGrid", {
            key: "StudentId",
            url: "{{ url('/ClassMember/Candidates') }}",
            params: function () { return { classroomId: classroomId(), sourceClassroomId: $("#filterSource").val() }; },
            columns: studentColumns(),
            onSelectionChanged: function (rows) { $("#btn_add").prop("disabled", rows.length < 1 || !classroomId()); }
        });
        gMemberGrid = HMA.createGrid("#memberGrid", {
            key: "StudentId",
            url: "{{ url('/ClassMember/Members') }}",
            params: function () { return { classroomId: classroomId() }; },
            columns: studentColumns(),
            onSelectionChanged: function (rows) { $("#btn_remove").prop("disabled", rows.length < 1); }
        });

        $("#filterYear").on("change", function () { fillClassrooms(); reload(); });
        $("#filterClassroom, #filterSource").on("change", reload);
        $("#btn_refresh").on("click", reload);
        reload();

        $("#btn_add").on("click", function () {
            var ids = gCandidateGrid.getSelectedRowKeys();
            showConfirmMesg("Masukkan " + ids.length + " siswa ke rombel " + HMA.escapeHtml($("#filterClassroom option:selected").text()) + "?", function () {
                HMA.post("{{ url('/ClassMember/Add') }}", { classroomId: classroomId(), studentIds: ids }).always(reload);
            }, "Konfirmasi");
        });
        $("#btn_remove").on("click", function () {
            var ids = gMemberGrid.getSelectedRowKeys();
            showConfirmMesg("Keluarkan " + ids.length + " siswa dari rombel " + HMA.escapeHtml($("#filterClassroom option:selected").text()) + "?", function () {
                HMA.post("{{ url('/ClassMember/Remove') }}", { classroomId: classroomId(), studentIds: ids }).always(reload);
            }, "Konfirmasi");
        });
    });
</script>
@endsection
