<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "Ujian",
        key: "ExamId",
        keyParam: "examId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Exam/Search') }}",
            getByKey: "{{ url('/Exam/GetByKey') }}",
            save: "{{ url('/Exam/AddEdit') }}",
            remove: "{{ url('/Exam/DeleteMultiple') }}",
            exportExcel: function () { return "{{ url('/Exam/DownloadExcel') }}?semesterId=" + $("#filterSemester").val(); }
        },
        params: function () { return { semesterId: $("#filterSemester").val() }; },
        columns: [
            { dataField: "ExamCode", caption: "Kode", width: 110 },
            { dataField: "ExamName", caption: "Nama Ujian" },
            { dataField: "ExamTypeName", caption: "Jenis" },
            { dataField: "SemesterLabel", caption: "Semester" },
            HMA.col.date("StartDate", "Mulai"),
            HMA.col.date("EndDate", "Selesai"),
            { dataField: "ScheduleCount", caption: "Jadwal", dataType: "number", alignment: "center", width: 80 },
            { dataField: "ParticipantCount", caption: "Peserta", dataType: "number", alignment: "center", width: 80 }
        ],
        focusAdd: "#addEditCode",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditSemester").val($("#filterSemester").val() || $("#addEditSemester option:first").val());
            $("#addEditCode").val("");
            $("#addEditCode").prop("disabled", false);
            $("#addEditName").val("");
            $("#addEditType").val("SAS");
            $("#addEditStart").val("");
            $("#addEditEnd").val("");
            $("#addEditNote").val("");
        },
        fillForm: function (data) {
            $("#addEditId").val(data.ExamId);
            $("#addEditSemester").val(data.SemesterId);
            $("#addEditCode").val(data.ExamCode);
            $("#addEditCode").prop("disabled", true);
            $("#addEditName").val(data.ExamName);
            $("#addEditType").val(data.ExamType);
            $("#addEditStart").val(data.StartDate ? String(data.StartDate).substr(0, 10) : "");
            $("#addEditEnd").val(data.EndDate ? String(data.EndDate).substr(0, 10) : "");
            $("#addEditNote").val(data.CardNote);
        },
        collectForm: function () {
            return {
                ExamId: $("#addEditId").val(),
                SemesterId: $("#addEditSemester").val(),
                ExamCode: $("#addEditCode").val(),
                ExamName: $("#addEditName").val(),
                ExamType: $("#addEditType").val(),
                StartDate: $("#addEditStart").val(),
                EndDate: $("#addEditEnd").val(),
                CardNote: $("#addEditNote").val()
            };
        }
    });

    $(function () {
        $("#filterSemester").on("change", gPage.search);
        gPage.grid.on("selectionChanged", function (e) { $("#btn_schedule").prop("disabled", e.selectedRowKeys.length !== 1); });
        $("#btn_schedule").on("click", function () { openSchedule(gPage.grid.getSelectedRowsData()[0]); });
    });
</script>
<script type="text/javascript">
    // ---------- Jadwal ujian (popup) ----------
    var gSchSubjects = @json($subjects);
    var gSchGrid = null, gSchRows = [], gSchExam = null, gSchKey = 0;

    function schGrid() {
        if (gSchGrid) return gSchGrid;
        var time = function (f, c) { return { dataField: f, caption: c, width: 90, alignment: "center", editorOptions: { mask: "00:00", useMaskedValue: true } }; };
        gSchGrid = $("#scheduleGrid").addClass("sg-grid").dxDataGrid({
            dataSource: [], keyExpr: "Key", showBorders: true, showRowLines: true, showColumnLines: true, height: 420,
            editing: { mode: "cell", allowUpdating: @json($edit), startEditAction: "click" },
            selection: { mode: "multiple", showCheckBoxesMode: "always" },
            sorting: { mode: "none" }, paging: { enabled: false },
            columns: [
                { dataField: "GradeLevel", caption: "Kelas", width: 90, lookup: { dataSource: [1, 2, 3, 4, 5, 6] } },
                { dataField: "SubjectId", caption: "Mata Pelajaran", lookup: { dataSource: gSchSubjects, valueExpr: "SubjectId", displayExpr: "SubjectName" }, editorOptions: { searchEnabled: true } },
                { dataField: "ExamDate", caption: "Tanggal", dataType: "date", format: "dd-MM-yyyy", width: 140, editorOptions: { dateSerializationFormat: "yyyy-MM-dd" } },
                time("StartTime", "Mulai"),
                time("EndTime", "Selesai")
            ]
        }).dxDataGrid("instance");
        return gSchGrid;
    }

    function schRender() { schGrid().option("dataSource", gSchRows); }

    function openSchedule(exam) {
        if (!exam) return;
        gSchExam = exam;
        $("#scheduleSub").text(exam.ExamCode + " · " + exam.ExamName + " (" + exam.StartDate + " s/d " + exam.EndDate + ")");
        HMA.post("{{ url('/Exam/Schedules') }}", { examId: exam.ExamId }, function (result) {
            gSchRows = $.map(result.data, function (r) { r.Key = ++gSchKey; return r; });
            $("#modalSchedule").modal("show").one("shown.bs.modal", function () { schRender(); schGrid().updateDimensions(); });
            schRender();
        }, { successMessage: false });
    }

    $(function () {
        $("#btn_schAdd").on("click", function () {
            var last = gSchRows[gSchRows.length - 1];
            gSchRows.push({ Key: ++gSchKey, GradeLevel: last ? last.GradeLevel : 1, SubjectId: null,
                ExamDate: last ? last.ExamDate : gSchExam.StartDate, StartTime: last ? last.StartTime : "07:30", EndTime: last ? last.EndTime : "09:00" });
            schRender();
        });
        $("#btn_schAddGrades").on("click", function () {
            var selected = schGrid().getSelectedRowsData();
            if (!selected.length) { showErrorMesgGrowl("Pilih baris yang akan disalin."); return; }
            $.each(selected, function (_, r) {
                for (var g = 1; g <= 6; g++) {
                    var exists = $.grep(gSchRows, function (x) { return x.GradeLevel === g && x.SubjectId === r.SubjectId; }).length;
                    if (!exists) gSchRows.push($.extend({}, r, { Key: ++gSchKey, GradeLevel: g }));
                }
            });
            schRender();
        });
        $("#btn_schDelete").on("click", function () {
            var keys = schGrid().getSelectedRowKeys();
            gSchRows = $.grep(gSchRows, function (r) { return keys.indexOf(r.Key) < 0; });
            schRender();
        });
        $("#btn_schSave").on("click", function () {
            schGrid().saveEditData();
            var rows = $.map(gSchRows, function (r) {
                return { GradeLevel: r.GradeLevel, SubjectId: r.SubjectId, ExamDate: r.ExamDate, StartTime: r.StartTime, EndTime: r.EndTime };
            });
            HMA.post("{{ url('/Exam/SaveSchedules') }}", { examId: gSchExam.ExamId, rows: JSON.stringify(rows) }, function () {
                $("#modalSchedule").modal("hide");
                gPage.search();
            });
        });
    });
</script>
