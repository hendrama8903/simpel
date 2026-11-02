<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "TP",
        key: "LearningObjectiveId",
        keyParam: "learningObjectiveId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/LearningObjective/Search') }}",
            getByKey: "{{ url('/LearningObjective/GetByKey') }}",
            save: "{{ url('/LearningObjective/AddEdit') }}",
            remove: "{{ url('/LearningObjective/DeleteMultiple') }}",
            exportExcel: function () { return "{{ url('/LearningObjective/DownloadExcel') }}?" + $.param({ subjectId: $("#filterSubject").val(), gradeLevel: $("#filterGrade").val(), semesterNo: $("#filterSemesterNo").val() }); }
        },
        params: function () { return { subjectId: $("#filterSubject").val(), gradeLevel: $("#filterGrade").val(), semesterNo: $("#filterSemesterNo").val() }; },
        columns: [
            { dataField: "SubjectName", caption: "Mata Pelajaran" },
            { dataField: "GradeLevel", caption: "Kelas", alignment: "center", width: 70 },
            { dataField: "Phase", caption: "Fase", alignment: "center", width: 60 },
            { dataField: "SemesterName", caption: "Semester", alignment: "center", width: 90 },
            { dataField: "ObjectiveCode", caption: "Kode", alignment: "center", width: 70 },
            { dataField: "Description", caption: "Deskripsi", width: 420 },
            HMA.col.status()
        ],
        focusAdd: "#addEditSubject",
        focusEdit: "#addEditDescription",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditSubject").val($("#filterSubject").val() || $("#addEditSubject option:first").val());
            $("#addEditGrade").val($("#filterGrade").val() || "1");
            $("#addEditSemesterNo").val($("#filterSemesterNo").val() || "1");
            $("#addEditCode").val("");
            $("#addEditDescription").val("");
            $("#addEditIsActive").prop("checked", true);
        },
        fillForm: function (data) {
            $("#addEditId").val(data.LearningObjectiveId);
            $("#addEditSubject").val(data.SubjectId);
            $("#addEditGrade").val(data.GradeLevel);
            $("#addEditSemesterNo").val(data.SemesterNo);
            $("#addEditCode").val(data.ObjectiveCode);
            $("#addEditDescription").val(data.Description);
            $("#addEditIsActive").prop("checked", data.IsActive);
        },
        collectForm: function () {
            return {
                LearningObjectiveId: $("#addEditId").val(),
                SubjectId: $("#addEditSubject").val(),
                GradeLevel: $("#addEditGrade").val(),
                SemesterNo: $("#addEditSemesterNo").val(),
                ObjectiveCode: $("#addEditCode").val(),
                Description: $("#addEditDescription").val(),
                IsActive: $("#addEditIsActive").is(":checked")
            };
        }
    });

    $(function () {
        $("#filterSubject, #filterGrade, #filterSemesterNo").on("change", gPage.search);
    });
</script>
