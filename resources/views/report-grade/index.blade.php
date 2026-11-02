{{--
    Nilai rapor per rombel x mapel. "Hitung" mengisi nilai akhir & deskripsi dari Input Nilai;
    nilai/deskripsi boleh diubah di grid lalu Simpan (ditandai manual, tidak ditimpa Hitung berikutnya).
--}}
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
            <div class="sg-field sg-field-grow">
                <label for="filterSubject">Mata Pelajaran</label>
                <select class="form-control" id="filterSubject"></select>
            </div>
        </div>
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_generate" disabled><i class="fa fa-calculator"></i> Hitung Nilai &amp; Deskripsi</button>
                    <label class="sg-check"><input type="checkbox" id="overwriteManual"> Timpa yang diubah manual</label>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_save" disabled><i class="fa fa-save"></i> Simpan Perubahan</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
            </div>
        </div>
        <div class="sg-info">KKTP {{ $kktp }}. Kolom Sumatif/STS/SAS/Hitungan = hasil Input Nilai saat ini; Nilai Akhir &amp; deskripsi = yang tersimpan dan dicetak di rapor.</div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<style>
    .rg-diff { color: #B36B00; font-weight: 700; }
    .rg-desc { white-space: normal !important; font-size: 12px; line-height: 1.35; }
</style>
<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gSheet, gLoaded = null;

    function params() {
        return { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val(), subjectId: $("#filterSubject").val() };
    }

    function fillSelect($el, items, value, text, empty) {
        $el.empty();
        $.each(items, function (_, x) { $el.append(new Option(x[text], x[value])); });
        if (!items.length) $el.append(new Option(empty, ""));
        $el.data("prev", $el.val());
    }

    function loadClassrooms(done) {
        HMA.post("{{ url('/ReportGrade/Classrooms') }}", { semesterId: $("#filterSemester").val() }, function (result) {
            fillSelect($("#filterClassroom"), result.data, "ClassroomId", "ClassroomName", "- tidak ada rombel -");
            loadSubjects(done);
        }, { successMessage: false, block: false });
    }

    function loadSubjects(done) {
        if (!$("#filterClassroom").val()) { fillSelect($("#filterSubject"), [], "", "", "-"); if (done) done(); return; }
        HMA.post("{{ url('/ReportGrade/Subjects') }}", { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val() }, function (result) {
            fillSelect($("#filterSubject"), result.data, "SubjectId", "SubjectName", "- tidak ada mapel -");
            if (done) done();
        }, { successMessage: false, block: false });
    }

    function load() {
        var p = params();
        $("#btn_generate").prop("disabled", !(p.classroomId && p.subjectId));
        if (!p.classroomId || !p.subjectId) { gSheet.load([]); gLoaded = null; return; }
        HMA.post("{{ url('/ReportGrade/Sheet') }}", p, function (result) {
            gLoaded = p;
            gSheet.load(result.data);
        }, { successMessage: false });
    }

    function save() {
        gSheet.commit();
        var rows = $.map(gSheet.dirtyRows(), function (r) {
            return { StudentId: r.StudentId, FinalScore: r.FinalScore === null || r.FinalScore === undefined ? "" : r.FinalScore,
                DescriptionHigh: r.DescriptionHigh || "", DescriptionLow: r.DescriptionLow || "" };
        });
        HMA.post("{{ url('/ReportGrade/Save') }}", $.extend({ rows: JSON.stringify(rows) }, gLoaded), function () { load(); });
    }

    $(function () {
        var num = function (field, caption) { return { dataField: field, caption: caption, dataType: "number", alignment: "center", width: 80, allowHeaderFiltering: false }; };
        gSheet = HMA.sheetGrid("#datagrid", {
            key: "StudentId",
            canEdit: gCanEdit,
            onDirty: function (n) { $("#btn_save").prop("disabled", n === 0); },
            gridOptions: { wordWrapEnabled: true },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "Nis", caption: "NIS", width: 100, fixed: true, fixedPosition: "left" },
                { dataField: "StudentName", caption: "Nama", width: 170, fixed: true, fixedPosition: "left" },
                num("SummativeAvg", "Sumatif"), num("Sts", "STS"), num("Sas", "SAS"), num("CalculatedScore", "Hitungan"),
                $.extend(num("FinalScore", "Nilai Akhir"), {
                    allowEditing: true, editorOptions: { min: 0, max: 100, showSpinButtons: false },
                    cellTemplate: function (c, o) {
                        if (o.value === null || o.value === undefined) return;
                        // Oranye = berbeda dengan hasil hitungan saat ini (nilai diubah manual / input nilai berubah setelah dihitung).
                        $("<span>").toggleClass("rg-diff", o.data.CalculatedScore !== null && Number(o.value) !== Number(o.data.CalculatedScore)).text(Math.round(o.value)).appendTo(c);
                    }
                }),
                { dataField: "DescriptionHigh", caption: "Capaian Tertinggi", allowEditing: true, width: 300, cssClass: "rg-desc", editorType: "dxTextArea", editorOptions: { height: 90 } },
                { dataField: "DescriptionLow", caption: "Perlu Bantuan / Peningkatan", allowEditing: true, width: 300, cssClass: "rg-desc", editorType: "dxTextArea", editorOptions: { height: 90 } },
                {
                    dataField: "IsManual", caption: "Status", alignment: "center", width: 100,
                    calculateCellValue: function (r) { return !r.IsSaved ? "Belum" : (r.IsManual ? "Manual" : "Otomatis"); },
                    cellTemplate: function (c, o) { $("<span>").addClass("sg-badge " + ({ Belum: "gray", Manual: "amber", Otomatis: "green" })[o.value]).text(o.value).appendTo(c); }
                }
            ]
        });

        loadClassrooms(load);
        HMA.guardFilter("#filterSemester", function () { return gSheet; }, function () { loadClassrooms(load); });
        HMA.guardFilter("#filterClassroom", function () { return gSheet; }, function () { loadSubjects(load); });
        HMA.guardFilter("#filterSubject", function () { return gSheet; }, load);
        $("#btn_refresh").on("click", function () { HMA.confirmDiscard(gSheet, load); });
        $("#btn_save").on("click", function () { showConfirmMesg("Simpan perubahan " + gSheet.dirtyCount() + " siswa? Baris tsb ditandai manual.", save, "Konfirmasi"); });
        $("#btn_generate").on("click", function () {
            var overwrite = $("#overwriteManual").is(":checked");
            HMA.confirmDiscard(gSheet, function () {
                showConfirmMesg("Hitung nilai akhir & deskripsi dari Input Nilai" + (overwrite ? ", termasuk menimpa yang diubah manual" : "") + "?", function () {
                    HMA.post("{{ url('/ReportGrade/Generate') }}", $.extend({ overwriteManual: overwrite ? 1 : 0 }, gLoaded), load);
                }, "Konfirmasi");
            });
        });
    });
</script>
@endsection
