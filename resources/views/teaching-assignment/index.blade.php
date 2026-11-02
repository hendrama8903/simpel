{{--
    Tugas mengajar: pilih semester & rombel, lalu isi guru per mapel di grid dan Simpan.
    Mapel "guru kelas" yang dikosongkan dipegang wali kelas rombel.
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
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_copy"><i class="fa fa-clone"></i> Salin dari Semester Lain</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Ekspor Excel</button>
            </div>
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>

<div class="modal fade sg-modal" id="modalCopy" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-clone"></i></div>
                <div>
                    <div class="dpf-header-title">Salin Tugas Mengajar</div>
                    <div class="dpf-header-sub">Ke semester yang sedang dipilih, semua rombel</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Dari Semester</div></div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="copySource">
                            @foreach ($semesters as $s)
                                <option value="{{ $s->SemesterId }}">{{ $s->Label }}</option>
                            @endforeach
                        </select>
                        <div class="dpf-hint">Hanya semester dalam tahun ajaran yang sama. Penugasan yang sudah ada di semester tujuan tidak ditimpa.</div>
                    </div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_doCopy"><i class="fa fa-clone"></i> Salin</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gSemesterYears = @json($semesterYears);
    var gClassrooms = @json($classrooms);
    var gTeachers = @json($teachers);
    var gSheet, gLoaded = null;

    function fillClassrooms() {
        var yearId = gSemesterYears[$("#filterSemester").val()];
        var $c = $("#filterClassroom").empty();
        $.each(gClassrooms, function (_, c) {
            if (c.academic_year_id === yearId) $c.append(new Option(c.classroom_name, c.classroom_id));
        });
        $c.data("prev", $c.val());
    }

    function load() {
        var params = { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val() };
        if (!params.classroomId) { gSheet.load([]); gLoaded = null; return; }
        HMA.post("{{ url('/TeachingAssignment/Sheet') }}", params, function (result) {
            gLoaded = params;
            gSheet.load(result.data);
            $("#btn_save").prop("disabled", true);
        }, { successMessage: false });
    }

    function save() {
        gSheet.commit();
        var rows = $.map(gSheet.dirtyRows(), function (r) { return { SubjectId: r.SubjectId, TeacherId: r.TeacherId || "" }; });
        HMA.post("{{ url('/TeachingAssignment/Save') }}", $.extend({ rows: JSON.stringify(rows) }, gLoaded), function () {
            gSheet.clearDirty();
        });
    }

    $(function () {
        gSheet = HMA.sheetGrid("#datagrid", {
            key: "SubjectId",
            canEdit: gCanEdit,
            onDirty: function (n) { $("#btn_save").prop("disabled", n === 0); },
            columns: [
                { dataField: "SubjectCode", caption: "Kode", width: 90 },
                { dataField: "SubjectName", caption: "Mata Pelajaran" },
                { dataField: "TaughtByName", caption: "Diajar Oleh", width: 120 },
                {
                    dataField: "TeacherId", caption: "Guru Pengajar", allowEditing: true, width: 320,
                    lookup: { dataSource: gTeachers, valueExpr: "TeacherId", displayExpr: "TeacherName", allowClearing: true },
                    editorOptions: { searchEnabled: true, showClearButton: true, placeholder: "Pilih guru" },
                    cellTemplate: function (c, o) {
                        if (o.displayValue) c.text(o.displayValue);
                        else if (o.data.DefaultTeacher) c.append($("<span class='text-muted'>").text("Wali kelas: " + o.data.DefaultTeacher));
                        else c.append($("<span class='text-muted'>").text("-"));
                    }
                }
            ]
        });

        fillClassrooms();
        load();

        HMA.guardFilter("#filterSemester", function () { return gSheet; }, function () { fillClassrooms(); load(); });
        HMA.guardFilter("#filterClassroom", function () { return gSheet; }, load);
        $("#btn_refresh").on("click", function () { HMA.confirmDiscard(gSheet, load); });
        $("#btn_save").on("click", function () { showConfirmMesg("Simpan " + gSheet.dirtyCount() + " perubahan?", save, "Konfirmasi"); });
        $("#btn_export").on("click", function () { window.location.href = "{{ url('/TeachingAssignment/DownloadExcel') }}?semesterId=" + $("#filterSemester").val(); });

        $("#btn_copy").on("click", function () { $("#modalCopy").modal("show"); });
        $("#btn_doCopy").on("click", function () {
            var data = { sourceSemesterId: $("#copySource").val(), targetSemesterId: $("#filterSemester").val() };
            HMA.post("{{ url('/TeachingAssignment/CopyFromSemester') }}", data, function () { $("#modalCopy").modal("hide"); load(); });
        });
    });
</script>
@endsection
