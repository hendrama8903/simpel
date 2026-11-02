{{--
    Wali kelas: tab Catatan Rapor (ketidakhadiran S/I/A, kokurikuler, catatan wali kelas, keputusan kenaikan di semester Genap)
    dan tab Ekstrakurikuler (predikat per ekskul). Masing-masing disimpan dengan tombol Simpan di tab tsb.
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
        </div>
    </div>

    <div class="sg-tabs" style="margin-top: 14px;">
        <button type="button" class="sg-tab active" data-pane="paneNotes"><i class="fa fa-sticky-note-o"></i> Catatan Rapor</button>
        <button type="button" class="sg-tab" data-pane="paneExtra"><i class="fa fa-futbol-o"></i> Ekstrakurikuler</button>
    </div>

    <div class="dp-card sg-pane" id="paneNotes">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_saveNotes" disabled><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_fillAttendance"><i class="fa fa-download"></i> Ambil S/I/A dari Presensi</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
            </div>
        </div>
        <div class="box-body"><div id="notesGrid"></div></div>
    </div>

    <div class="dp-card sg-pane" id="paneExtra" style="display: none;">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_saveExtra" disabled><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_fillMandatory"><i class="fa fa-magic"></i> Isi Ekskul Wajib = Baik</button>
                @endif
            </div>
        </div>
        <div class="sg-info">Kosongkan predikat bila siswa tidak mengikuti ekskul tsb.</div>
        <div class="box-body"><div id="extraGrid"></div></div>
    </div>
</section>
@endsection

@section('scripts')
<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gDecisions = $.map(@json($decisions), function (name, code) { return { Code: code, Name: name }; });
    var gPredicates = @json($predicates);
    var gNotes, gExtra, gLoaded = null, gSemesterNo = 1, gExtras = [];
    var gBoth = { dirtyCount: function () { return (gNotes ? gNotes.dirtyCount() : 0) + (gExtra ? gExtra.dirtyCount() : 0); } };

    function loadClassrooms(done) {
        HMA.post("{{ url('/Homeroom/Classrooms') }}", { semesterId: $("#filterSemester").val() }, function (result) {
            var $c = $("#filterClassroom").empty();
            gSemesterNo = result.data.semesterNo;
            $.each(result.data.classrooms, function (_, c) { $c.append(new Option(c.ClassroomName, c.ClassroomId)); });
            if (!$c.children().length) $c.append(new Option("- bukan wali kelas -", ""));
            $c.data("prev", $c.val());
            if (done) done();
        }, { successMessage: false, block: false });
    }

    function load() {
        var p = { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val() };
        gNotes.grid.columnOption("PromotionDecision", "visible", gSemesterNo === 2);
        if (!p.classroomId) { gNotes.load([]); gExtra.load([]); gLoaded = null; return; }
        HMA.post("{{ url('/Homeroom/Sheet') }}", p, function (result) {
            gLoaded = p;
            gNotes.load(result.data);
        }, { successMessage: false });
        HMA.post("{{ url('/Homeroom/ExtracurricularSheet') }}", p, function (result) {
            gExtras = result.data.extracurriculars;
            var columns = [
                HMA.col.rowNumber(),
                { dataField: "Nis", caption: "NIS", width: 100, fixed: true, fixedPosition: "left" },
                { dataField: "StudentName", caption: "Nama", fixed: true, fixedPosition: "left" }
            ];
            $.each(gExtras, function (_, e) {
                columns.push({
                    dataField: "E" + e.ExtracurricularId, caption: e.ExtracurricularName + (e.IsMandatory ? " (wajib)" : ""), width: 150,
                    allowEditing: true, lookup: { dataSource: gPredicates, allowClearing: true }, editorOptions: { showClearButton: true, placeholder: "-" }
                });
            });
            gExtra.grid.option("columns", $.map(columns, function (c) {
                c = HMA.relaxWidth(c);
                if (c.allowEditing && gCanEdit) c.cssClass = "sg-edit-col";
                return c;
            }));
            gExtra.load(result.data.rows);
        }, { successMessage: false, block: false });
    }

    $(function () {
        var count = function (field, caption) {
            return { dataField: field, caption: caption, dataType: "number", alignment: "center", width: 70, allowEditing: true, allowHeaderFiltering: false, editorOptions: { min: 0, showSpinButtons: false } };
        };
        gNotes = HMA.sheetGrid("#notesGrid", {
            key: "StudentId",
            canEdit: gCanEdit,
            onDirty: function (n) { $("#btn_saveNotes").prop("disabled", n === 0); },
            gridOptions: { wordWrapEnabled: true },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "Nis", caption: "NIS", width: 100, fixed: true, fixedPosition: "left" },
                { dataField: "StudentName", caption: "Nama", width: 170, fixed: true, fixedPosition: "left" },
                count("Sick", "Sakit"), count("Permit", "Izin"), count("Absent", "Alpa"),
                { dataField: "CocurricularNote", caption: "Kokurikuler", width: 260, allowEditing: true, editorType: "dxTextArea", editorOptions: { height: 80 } },
                { dataField: "HomeroomNote", caption: "Catatan Wali Kelas", width: 300, allowEditing: true, editorType: "dxTextArea", editorOptions: { height: 80 } },
                {
                    dataField: "PromotionDecision", caption: "Keputusan", width: 140, allowEditing: true,
                    lookup: { dataSource: gDecisions, valueExpr: "Code", displayExpr: "Name", allowClearing: true }
                }
            ]
        });
        gExtra = HMA.sheetGrid("#extraGrid", {
            key: "StudentId",
            canEdit: gCanEdit,
            onDirty: function (n) { $("#btn_saveExtra").prop("disabled", n === 0); },
            columns: [{ dataField: "StudentName", caption: "Nama" }]
        });

        loadClassrooms(load);
        HMA.guardFilter("#filterSemester", function () { return gBoth; }, function () { loadClassrooms(load); });
        HMA.guardFilter("#filterClassroom", function () { return gBoth; }, load);
        $("#btn_refresh").on("click", function () { HMA.confirmDiscard(gBoth, load); });

        $("#btn_saveNotes").on("click", function () {
            gNotes.commit();
            var rows = $.map(gNotes.dirtyRows(), function (r) {
                return { StudentId: r.StudentId, Sick: r.Sick || 0, Permit: r.Permit || 0, Absent: r.Absent || 0,
                    CocurricularNote: r.CocurricularNote || "", HomeroomNote: r.HomeroomNote || "", PromotionDecision: r.PromotionDecision || "" };
            });
            HMA.post("{{ url('/Homeroom/Save') }}", $.extend({ rows: JSON.stringify(rows) }, gLoaded), function () { gNotes.clearDirty(); });
        });
        $("#btn_fillAttendance").on("click", function () {
            if (!gLoaded) return;
            HMA.post("{{ url('/Homeroom/FillAttendance') }}", gLoaded, function (result) {
                $.each(gNotes.rows, function (_, r) {
                    var t = result.data[r.StudentId] || { S: 0, I: 0, A: 0 };
                    r.Sick = t.S; r.Permit = t.I; r.Absent = t.A;
                    gNotes.markDirty(r);
                });
                gNotes.grid.repaint();
                showSuccessMesgGrowl("Jumlah S/I/A diisi dari presensi. Klik Simpan untuk menyimpan.");
            }, { successMessage: false });
        });

        $("#btn_saveExtra").on("click", function () {
            gExtra.commit();
            var rows = $.map(gExtra.dirtyRows(), function (r) {
                var values = {};
                $.each(gExtras, function (_, e) { values[e.ExtracurricularId] = r["E" + e.ExtracurricularId] || ""; });
                return { StudentId: r.StudentId, Values: values };
            });
            HMA.post("{{ url('/Homeroom/SaveExtracurricular') }}", $.extend({ rows: JSON.stringify(rows) }, gLoaded), function () { gExtra.clearDirty(); });
        });
        $("#btn_fillMandatory").on("click", function () {
            $.each(gExtra.rows, function (_, r) {
                $.each(gExtras, function (_, e) {
                    if (e.IsMandatory && !r["E" + e.ExtracurricularId]) { r["E" + e.ExtracurricularId] = "Baik"; gExtra.markDirty(r); }
                });
            });
            gExtra.grid.repaint();
        });

        $(".sg-tab").on("click", function () {
            $(".sg-tab").removeClass("active");
            $(this).addClass("active");
            $(".sg-pane").hide();
            $("#" + $(this).data("pane")).show();
            (this.dataset.pane === "paneExtra" ? gExtra : gNotes).grid.option("height", HMA.gridHeight(this.dataset.pane === "paneExtra" ? "#extraGrid" : "#notesGrid"));
        });
    });
</script>
@endsection
