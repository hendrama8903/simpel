{{--
    Input nilai: pilih semester, rombel & mapel. Satu kolom = satu penilaian (Formatif per TP, Sumatif lingkup materi, STS, SAS).
    Nilai 0-100 diisi langsung di grid lalu Simpan. Kolom ditambah/diubah/dihapus lewat tombol Kolom Penilaian.
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
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_save" disabled><i class="fa fa-save"></i> Simpan Nilai</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_addColumn" disabled><i class="fa fa-plus"></i> Tambah Kolom</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_columns" disabled><i class="fa fa-columns"></i> Kolom Penilaian</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Ekspor Excel</button>
            </div>
        </div>
        <div class="sg-info" id="sheetInfo"></div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>

<div class="modal fade sg-modal" id="modalColumn" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-columns"></i></div>
                <div>
                    <div class="dpf-header-title" id="columnTitle">Tambah Kolom Penilaian</div>
                    <div class="dpf-header-sub" id="columnSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <input type="hidden" id="colId">
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Jenis <span class="text-red">*</span></div></div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="colType">
                            @foreach ($types as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="dpf-row" id="colObjectiveRow">
                    <div class="dpf-row-left"><div><div class="dpf-label">Tujuan Pembelajaran</div><div class="dpf-sub">Wajib untuk formatif</div></div></div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="colObjective"></select>
                        <div class="dpf-hint" id="colObjectiveHint"></div>
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left"><div><div class="dpf-label">Nama Kolom</div><div class="dpf-sub">Kosongkan = otomatis</div></div></div>
                    <div class="dpf-row-right"><input class="form-control" id="colName" maxlength="50" placeholder="mis. F-TP1, S-Bab 2"></div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Tanggal</div></div>
                    <div class="dpf-row-right"><input class="form-control" type="date" id="colDate"></div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_saveColumn"><i class="fa fa-save"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sg-modal" id="modalColumns" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-columns"></i></div>
                <div>
                    <div class="dpf-header-title">Kolom Penilaian</div>
                    <div class="dpf-header-sub">Menghapus kolom ikut menghapus semua nilainya</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body"><table class="table table-condensed" id="columnList" style="margin: 12px 0;"></table></div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .as-head-FORMATIF { color: #2450D8; }
    .as-head-SUMATIF { color: #1E9E63; }
    .as-head-STS, .as-head-SAS { color: #B36B00; }
    .as-low { color: #C62828; font-weight: 700; }
</style>
<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gSheet, gLoaded = null, gColumns = [], gObjectives = [];

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
        HMA.post("{{ url('/Assessment/Classrooms') }}", { semesterId: $("#filterSemester").val() }, function (result) {
            fillSelect($("#filterClassroom"), result.data, "ClassroomId", "ClassroomName", "- tidak ada rombel -");
            loadSubjects(done);
        }, { successMessage: false, block: false });
    }

    function loadSubjects(done) {
        if (!$("#filterClassroom").val()) { fillSelect($("#filterSubject"), [], "", "", "-"); if (done) done(); return; }
        HMA.post("{{ url('/Assessment/Subjects') }}", { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val() }, function (result) {
            fillSelect($("#filterSubject"), result.data, "SubjectId", "SubjectName", "- tidak ada mapel -");
            if (done) done();
        }, { successMessage: false, block: false });
    }

    function buildColumns() {
        var columns = [
            HMA.col.rowNumber(),
            { dataField: "Nis", caption: "NIS", width: 100, fixed: true, fixedPosition: "left" },
            { dataField: "StudentName", caption: "Nama", fixed: true, fixedPosition: "left" }
        ];
        $.each(gColumns, function (_, c) {
            columns.push({
                dataField: "A" + c.AssessmentId, caption: c.AssessmentName, dataType: "number", alignment: "center", width: 90,
                allowEditing: true, allowHeaderFiltering: false,
                editorOptions: { min: 0, max: 100, showSpinButtons: false },
                validationRules: [{ type: "range", min: 0, max: 100, message: "Nilai 0-100" }],
                headerCellTemplate: function (h) {
                    $("<span>").addClass("as-head-" + c.AssessmentType).text(c.AssessmentName)
                        .attr("title", c.AssessmentTypeName + (c.ObjectiveDescription ? ": " + c.ObjectiveCode + " " + c.ObjectiveDescription : "")).appendTo(h);
                },
                cellTemplate: function (cell, o) {
                    if (o.value === null || o.value === undefined || o.value === "") return;
                    $("<span>").toggleClass("as-low", o.value < gKktp).text(o.value).appendTo(cell);
                }
            });
        });
        return columns;
    }
    var gKktp = 0;

    function load() {
        var p = params();
        var ready = !!(p.classroomId && p.subjectId);
        $("#btn_addColumn, #btn_columns").prop("disabled", !ready);
        if (!ready) { gColumns = []; gSheet.grid.option("columns", buildColumns()); gSheet.load([]); gLoaded = null; $("#sheetInfo").text(""); return; }
        HMA.post("{{ url('/Assessment/Sheet') }}", p, function (result) {
            gLoaded = p;
            gColumns = result.data.columns;
            gObjectives = result.data.objectives;
            gSheet.grid.option("columns", $.map(buildColumns(), HMA.relaxWidth));
            gSheet.load(result.data.rows);
            $("#sheetInfo").text(gColumns.length
                ? gColumns.length + " kolom penilaian. Arahkan kursor ke judul kolom untuk melihat TP-nya. Nilai di bawah KKTP berwarna merah."
                : "Belum ada kolom penilaian. Klik Tambah Kolom untuk mulai mengisi nilai.");
        }, { successMessage: false });
    }

    function save() {
        gSheet.commit();
        var rows = $.map(gSheet.dirtyRows(), function (r) {
            var scores = {};
            $.each(gColumns, function (_, c) { var v = r["A" + c.AssessmentId]; scores[c.AssessmentId] = v === null || v === undefined ? "" : v; });
            return { StudentId: r.StudentId, Scores: scores };
        });
        HMA.post("{{ url('/Assessment/SaveScores') }}", $.extend({ rows: JSON.stringify(rows) }, gLoaded), function () { gSheet.clearDirty(); });
    }

    function openColumn(c) {
        $("#columnTitle").text(c ? "Ubah Kolom Penilaian" : "Tambah Kolom Penilaian");
        $("#columnSub").text($("#filterClassroom option:selected").text() + " · " + $("#filterSubject option:selected").text());
        var $o = $("#colObjective").empty().append(new Option("-", ""));
        $.each(gObjectives, function (_, o) { $o.append(new Option(o.Text, o.LearningObjectiveId)); });
        $("#colObjectiveHint").text(gObjectives.length ? "" : "Belum ada TP untuk mapel, kelas & semester ini. Tambahkan di menu Tujuan Pembelajaran.");
        $("#colId").val(c ? c.AssessmentId : "");
        $("#colType").val(c ? c.AssessmentType : "FORMATIF").trigger("change");
        $("#colObjective").val(c && c.LearningObjectiveId ? c.LearningObjectiveId : "");
        $("#colName").val(c ? c.AssessmentName : "");
        $("#colDate").val(c ? (c.AssessmentDate || "") : new Date().toISOString().substr(0, 10));
        $("#modalColumns").modal("hide");
        $("#modalColumn").modal("show");
    }

    function renderColumnList() {
        var $t = $("#columnList").empty().append("<tr><th>Nama</th><th>Jenis</th><th>TP</th><th>Tanggal</th><th></th></tr>");
        if (!gColumns.length) $t.append($("<tr><td colspan='5' class='text-muted'>Belum ada kolom.</td></tr>"));
        $.each(gColumns, function (_, c) {
            var $actions = $("<td class='text-right'>")
                .append($("<button type='button' class='dp-btn dp-btn-outline'>").text("Ubah").on("click", function () { openColumn(c); }))
                .append(" ")
                .append($("<button type='button' class='dp-btn dp-btn-outline-danger'>").text("Hapus").on("click", function () {
                    showConfirmMesg("Hapus kolom " + HMA.escapeHtml(c.AssessmentName) + " beserta semua nilainya?", function () {
                        HMA.post("{{ url('/Assessment/DeleteColumn') }}", $.extend({ assessmentId: c.AssessmentId }, gLoaded), function () {
                            $("#modalColumns").modal("hide");
                            load();
                        });
                    }, "Konfirmasi");
                }));
            $t.append($("<tr>").append($("<td>").text(c.AssessmentName), $("<td>").text(c.AssessmentTypeName),
                $("<td>").text(c.ObjectiveCode ? c.ObjectiveCode + " - " + c.ObjectiveDescription : "-"), $("<td>").text(c.AssessmentDate || "-"), $actions));
        });
    }

    $(function () {
        gSheet = HMA.sheetGrid("#datagrid", {
            key: "StudentId",
            canEdit: gCanEdit,
            columns: buildColumns(),
            onDirty: function (n) { $("#btn_save").prop("disabled", n === 0); }
        });
        gKktp = @json(\App\Support\Academic::school()->kktp);

        loadClassrooms(load);
        HMA.guardFilter("#filterSemester", function () { return gSheet; }, function () { loadClassrooms(load); });
        HMA.guardFilter("#filterClassroom", function () { return gSheet; }, function () { loadSubjects(load); });
        HMA.guardFilter("#filterSubject", function () { return gSheet; }, load);
        $("#btn_refresh").on("click", function () { HMA.confirmDiscard(gSheet, load); });
        $("#btn_save").on("click", function () { showConfirmMesg("Simpan nilai " + gSheet.dirtyCount() + " siswa?", save, "Konfirmasi"); });
        $("#btn_export").on("click", function () { if (gLoaded) window.location.href = "{{ url('/Assessment/DownloadExcel') }}?" + $.param(gLoaded); });

        $("#btn_addColumn").on("click", function () { HMA.confirmDiscard(gSheet, function () { openColumn(null); }); });
        $("#btn_columns").on("click", function () { HMA.confirmDiscard(gSheet, function () { renderColumnList(); $("#modalColumns").modal("show"); }); });
        $("#colType").on("change", function () { $("#colObjectiveRow").toggle(this.value === "FORMATIF" || this.value === "SUMATIF"); });
        $("#btn_saveColumn").on("click", function () {
            var data = $.extend({
                AssessmentId: $("#colId").val(), AssessmentType: $("#colType").val(), LearningObjectiveId: $("#colObjective").val(),
                AssessmentName: $("#colName").val(), AssessmentDate: $("#colDate").val()
            }, gLoaded);
            HMA.post("{{ url('/Assessment/SaveColumn') }}", data, function () { $("#modalColumn").modal("hide"); load(); });
        });
    });
</script>
@endsection
