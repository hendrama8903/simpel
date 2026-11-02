{{--
    Jadwal pelajaran: matriks jam ke- x hari per rombel. Isi jam mulai/selesai dan mapel per hari, lalu Simpan.
    Guru diambil dari Tugas Mengajar (mapel guru kelas = wali kelas).
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
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_save" disabled><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_addPeriod"><i class="fa fa-plus"></i> Tambah Jam</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_print"><i class="fa fa-print"></i> Cetak PDF</button>
            </div>
        </div>
        <div class="sg-info">Jam mulai/selesai format HH:MM, mis. 07:00. Jam istirahat cukup dikosongkan mapelnya.</div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gDays = @json($days);
    var gSubjects = @json($subjects);
    var gSheet, gLoaded = null;

    function loadClassrooms(done) {
        HMA.post("{{ url('/Schedule/Classrooms') }}", { semesterId: $("#filterSemester").val() }, function (result) {
            var $c = $("#filterClassroom").empty();
            $.each(result.data, function (_, c) { $c.append(new Option(c.ClassroomName, c.ClassroomId)); });
            if (!result.data.length) $c.append(new Option("- tidak ada rombel -", ""));
            $c.data("prev", $c.val());
            if (done) done();
        }, { successMessage: false, block: false });
    }

    function load() {
        var params = { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val() };
        if (!params.classroomId) { gSheet.load([]); gLoaded = null; return; }
        HMA.post("{{ url('/Schedule/Sheet') }}", params, function (result) {
            gLoaded = params;
            gSheet.load(result.data);
        }, { successMessage: false });
    }

    function save() {
        gSheet.commit();
        HMA.post("{{ url('/Schedule/Save') }}", $.extend({ rows: JSON.stringify(gSheet.rows) }, gLoaded), function () {
            gSheet.clearDirty();
        });
    }

    $(function () {
        var timeCol = function (field, caption) {
            return { dataField: field, caption: caption, width: 80, alignment: "center", allowEditing: true, editorOptions: { mask: "00:00", useMaskedValue: true } };
        };
        var columns = [
            { dataField: "PeriodNo", caption: "Jam Ke", alignment: "center", width: 70, fixed: true, fixedPosition: "left" },
            timeCol("StartTime", "Mulai"),
            timeCol("EndTime", "Selesai")
        ];
        $.each(gDays, function (day, name) {
            columns.push({
                dataField: "D" + day, caption: name, allowEditing: true, width: 170, allowSorting: false,
                lookup: { dataSource: gSubjects, valueExpr: "SubjectId", displayExpr: "SubjectName", allowClearing: true },
                editorOptions: { searchEnabled: true, showClearButton: true, placeholder: "-" }
            });
        });

        gSheet = HMA.sheetGrid("#datagrid", {
            key: "PeriodNo",
            canEdit: gCanEdit,
            columns: columns,
            filterToggle: false,
            onDirty: function (n) { $("#btn_save").prop("disabled", n === 0); },
            gridOptions: { sorting: { mode: "none" }, headerFilter: { visible: false } }
        });

        loadClassrooms(load);
        HMA.guardFilter("#filterSemester", function () { return gSheet; }, function () { loadClassrooms(load); });
        HMA.guardFilter("#filterClassroom", function () { return gSheet; }, load);
        $("#btn_refresh").on("click", function () { HMA.confirmDiscard(gSheet, load); });
        $("#btn_save").on("click", function () { showConfirmMesg("Simpan jadwal rombel " + HMA.escapeHtml($("#filterClassroom option:selected").text()) + "?", save, "Konfirmasi"); });
        $("#btn_addPeriod").on("click", function () {
            if (!gLoaded) return;
            var last = gSheet.rows[gSheet.rows.length - 1];
            var row = { PeriodNo: last ? last.PeriodNo + 1 : 1, StartTime: last ? last.EndTime : null, EndTime: null };
            $.each(gDays, function (day) { row["D" + day] = null; });
            gSheet.rows.push(row);
            gSheet.refresh();
            gSheet.markDirty(row);
        });
        $("#btn_print").on("click", function () {
            if (!gLoaded) return;
            window.open("{{ url('/Schedule/Print') }}?" + $.param(gLoaded), "_blank");
        });
    });
</script>
@endsection
