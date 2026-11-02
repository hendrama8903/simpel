<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "Mutasi",
        key: "StudentExitId",
        keyParam: "studentExitId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/StudentExit/Search') }}",
            getByKey: "{{ url('/StudentExit/GetByKey') }}",
            save: "{{ url('/StudentExit/AddEdit') }}",
            remove: "{{ url('/StudentExit/DeleteMultiple') }}",
            exportExcel: function () { return "{{ url('/StudentExit/DownloadExcel') }}?" + $.param({ exitType: $("#filterType").val(), year: $("#filterYear").val() }); }
        },
        params: function () { return { exitType: $("#filterType").val(), year: $("#filterYear").val() }; },
        columns: [
            { dataField: "Nis", caption: "NIS" },
            { dataField: "StudentName", caption: "Nama" },
            { dataField: "LastClassroom", caption: "Rombel Terakhir" },
            { dataField: "ExitTypeName", caption: "Jenis" },
            HMA.col.date("ExitDate", "Tanggal"),
            { dataField: "DestinationSchool", caption: "Sekolah Tujuan" },
            { dataField: "Reason", caption: "Keterangan" }
        ],
        focusAdd: "#addEditStudent",
        focusEdit: "#addEditType",
        resetForm: function () {
            $("#addEditId").val("");
            HMA.setSelect2Value("#addEditStudent", null);
            $("#addEditStudent").prop("disabled", false);
            $("#addEditType").val("MUTASI");
            $("#addEditDate").val(new Date().toISOString().substr(0, 10));
            $("#addEditDestination").val("");
            $("#addEditReason").val("");
        },
        fillForm: function (data) {
            $("#addEditId").val(data.StudentExitId);
            HMA.setSelect2Value("#addEditStudent", data.StudentId, data.StudentText);
            $("#addEditStudent").prop("disabled", true);
            $("#addEditType").val(data.ExitType);
            $("#addEditDate").val(data.ExitDate ? String(data.ExitDate).substr(0, 10) : "");
            $("#addEditDestination").val(data.DestinationSchool);
            $("#addEditReason").val(data.Reason);
        },
        collectForm: function () {
            return {
                StudentExitId: $("#addEditId").val(),
                StudentId: $("#addEditStudent").val(),
                ExitType: $("#addEditType").val(),
                ExitDate: $("#addEditDate").val(),
                DestinationSchool: $("#addEditDestination").val(),
                Reason: $("#addEditReason").val()
            };
        }
    });

    $(function () {
        HMA.select2("#addEditStudent", "{{ url('/Lookup/Student') }}", "Pilih siswa", "#modalAddEdit");
        $("#filterType, #filterYear").on("change", gPage.search);

        $("#btn_graduate").on("click", function () {
            $("#gradDate").val(new Date().toISOString().substr(0, 10));
            $("#modalGraduate").modal("show");
        });
        $("#btn_doGraduate").on("click", function () {
            var data = { classroomId: $("#gradClassroom").val(), exitDate: $("#gradDate").val() };
            if (!data.classroomId) { showErrorMesgGrowl("Pilih rombel kelas 6."); return; }
            showConfirmMesg("Luluskan semua siswa aktif rombel " + HMA.escapeHtml($("#gradClassroom option:selected").text()) + "?", function () {
                HMA.post("{{ url('/StudentExit/Graduate') }}", data, function () {
                    $("#modalGraduate").modal("hide");
                    $("#filterType").val("");
                    $("#filterYear").val(data.exitDate.substr(0, 4));
                    gPage.search();
                });
            }, "Konfirmasi");
        });
    });
</script>
