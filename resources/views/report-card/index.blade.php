{{-- Cetak rapor: pilih rombel, periksa kelengkapan per siswa, cetak PDF siswa terpilih atau satu rombel. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="sg-filterbar">
            <div class="sg-field">
                <label for="filterSemester">Semester</label>
                <select class="form-control" id="filterSemester">
                    @foreach ($semesters as $s)
                        <option value="{{ $s->SemesterId }}" @selected($s->IsActive)>{{ $s->Label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sg-field">
                <label for="filterClassroom">Rombel</label>
                <select class="form-control" id="filterClassroom"></select>
            </div>
        </div>
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($print)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_printSelected" disabled><i class="fa fa-print"></i> Cetak Terpilih</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_printAll"><i class="fa fa-print"></i> Cetak Satu Rombel</button>
                    <label class="sg-check"><input type="checkbox" id="withCover"> Sertakan sampul &amp; identitas</label>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
            </div>
        </div>
        <div class="sg-info">Nilai diambil dari menu Nilai Rapor; ketidakhadiran, catatan &amp; ekstrakurikuler dari menu Wali Kelas. Tanggal rapor diatur di Tahun Ajaran.</div>
        <div class="box-body"><div id="datagrid"></div></div>
    </div>
</section>
@endsection

@section('scripts')
<script type="text/javascript">
    var gGrid;

    function loadClassrooms(done) {
        HMA.post("{{ url('/ReportCard/Classrooms') }}", { semesterId: $("#filterSemester").val() }, function (result) {
            var $c = $("#filterClassroom").empty();
            $.each(result.data, function (_, c) { $c.append(new Option(c.ClassroomName, c.ClassroomId)); });
            if (!$c.children().length) $c.append(new Option("- bukan wali kelas -", ""));
            if (done) done();
        }, { successMessage: false, block: false });
    }

    function reload() { gGrid.clearSelection(); gGrid.refresh(); }

    function print(studentIds) {
        var p = { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val(), studentIds: studentIds.join(","), cover: $("#withCover").is(":checked") ? 1 : 0 };
        if (!p.classroomId) return;
        window.open("{{ url('/ReportCard/Print') }}?" + $.param(p), "_blank");
    }

    $(function () {
        gGrid = HMA.createGrid("#datagrid", {
            key: "StudentId",
            url: "{{ url('/ReportCard/Search') }}",
            params: function () { return { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val() }; },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "Nis", caption: "NIS" },
                { dataField: "Nisn", caption: "NISN" },
                { dataField: "StudentName", caption: "Nama" },
                {
                    dataField: "GradeCount", caption: "Nilai Mapel", alignment: "center",
                    cellTemplate: function (c, o) {
                        $("<span>").addClass("sg-badge " + (o.value >= o.data.SubjectCount ? "green" : (o.value ? "amber" : "red")))
                            .text(o.value + " / " + o.data.SubjectCount).appendTo(c);
                    }
                },
                HMA.col.status("HasNote", "Catatan Wali Kelas", "Sudah", "Belum")
            ],
            onSelectionChanged: function (rows) { $("#btn_printSelected").prop("disabled", rows.length < 1); }
        });

        loadClassrooms(reload);
        $("#filterSemester").on("change", function () { loadClassrooms(reload); });
        $("#filterClassroom").on("change", reload);
        $("#btn_refresh").on("click", reload);
        $("#btn_printSelected").on("click", function () { print(gGrid.getSelectedRowKeys()); });
        $("#btn_printAll").on("click", function () { print([]); });
    });
</script>
@endsection
