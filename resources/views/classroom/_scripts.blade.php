<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "Rombel",
        key: "ClassroomId",
        keyParam: "classroomId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Classroom/Search') }}",
            getByKey: "{{ url('/Classroom/GetByKey') }}",
            save: "{{ url('/Classroom/AddEdit') }}",
            remove: "{{ url('/Classroom/DeleteMultiple') }}",
            exportExcel: function () { return "{{ url('/Classroom/DownloadExcel') }}?academicYearId=" + $("#filterYear").val(); }
        },
        params: function () { return { academicYearId: $("#filterYear").val() }; },
        columns: [
            { dataField: "GradeLevel", caption: "Tingkat", dataType: "number", alignment: "center", width: 80 },
            { dataField: "Phase", caption: "Fase", alignment: "center", width: 70 },
            { dataField: "ClassroomName", caption: "Rombel" },
            { dataField: "HomeroomTeacherName", caption: "Wali Kelas" },
            { dataField: "RoomName", caption: "Ruangan" },
            { dataField: "StudentCount", caption: "Jumlah Siswa", dataType: "number", alignment: "center" }
        ],
        focusAdd: "#addEditGrade",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditYear").val($("#filterYear").val()).prop("disabled", false);
            $("#addEditGrade").val("1");
            $("#addEditName").val("");
            HMA.setSelect2Value("#addEditHomeroom", null);
            $("#addEditRoom").val("");
        },
        fillForm: function (data) {
            $("#addEditId").val(data.ClassroomId);
            $("#addEditYear").val(data.AcademicYearId).prop("disabled", true);
            $("#addEditGrade").val(data.GradeLevel);
            $("#addEditName").val(data.ClassroomName);
            HMA.setSelect2Value("#addEditHomeroom", data.HomeroomTeacherId, data.HomeroomTeacherName);
            $("#addEditRoom").val(data.RoomId);
        },
        collectForm: function () {
            return {
                ClassroomId: $("#addEditId").val(),
                AcademicYearId: $("#addEditYear").val(),
                GradeLevel: $("#addEditGrade").val(),
                ClassroomName: $("#addEditName").val(),
                HomeroomTeacherId: $("#addEditHomeroom").val(),
                RoomId: $("#addEditRoom").val()
            };
        }
    });

    $(function () {
        HMA.select2("#addEditHomeroom", "{{ url('/Lookup/Teacher') }}", "Pilih wali kelas", "#modalAddEdit");
        $("#filterYear").on("change", gPage.search);

        $("#btn_copy").on("click", function () {
            $("#copyTarget").val($("#filterYear").val());
            $("#modalCopy").modal("show");
        });
        $("#btn_doCopy").on("click", function () {
            var data = { sourceYearId: $("#copySource").val(), targetYearId: $("#copyTarget").val() };
            showConfirmMesg("Salin rombel dari " + $("#copySource option:selected").text() + " ke " + $("#copyTarget option:selected").text() + "?", function () {
                HMA.post("{{ url('/Classroom/CopyFrom') }}", data, function () {
                    $("#modalCopy").modal("hide");
                    $("#filterYear").val(data.targetYearId);
                    gPage.search();
                });
            }, "Konfirmasi");
        });
    });
</script>
