<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "Mapel",
        key: "SubjectId",
        keyParam: "subjectId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Subject/Search') }}",
            getByKey: "{{ url('/Subject/GetByKey') }}",
            save: "{{ url('/Subject/AddEdit') }}",
            remove: "{{ url('/Subject/DeleteMultiple') }}",
            exportExcel: "{{ url('/Subject/DownloadExcel') }}"
        },
        columns: [
            { dataField: "ReportOrder", caption: "Urutan", dataType: "number", alignment: "center", width: 80, sortOrder: "asc" },
            { dataField: "SubjectCode", caption: "Kode", width: 90 },
            { dataField: "SubjectName", caption: "Nama Mapel" },
            { dataField: "SubjectGroupName", caption: "Kelompok" },
            { dataField: "TaughtByName", caption: "Diajar Oleh" },
            HMA.col.status()
        ],
        focusAdd: "#addEditCode",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditCode").val("").prop("disabled", false);
            $("#addEditName").val("");
            $("#addEditGroup").val("UMUM");
            $("#addEditTaughtBy").val("GURU_KELAS");
            $("#addEditOrder").val("");
            $("#addEditIsActive").prop("checked", true);
        },
        fillForm: function (data) {
            $("#addEditId").val(data.SubjectId);
            $("#addEditCode").val(data.SubjectCode).prop("disabled", true);
            $("#addEditName").val(data.SubjectName);
            $("#addEditGroup").val(data.SubjectGroup);
            $("#addEditTaughtBy").val(data.TaughtBy);
            $("#addEditOrder").val(data.ReportOrder);
            $("#addEditIsActive").prop("checked", data.IsActive);
        },
        collectForm: function () {
            return {
                SubjectId: $("#addEditId").val(),
                SubjectCode: $("#addEditCode").val(),
                SubjectName: $("#addEditName").val(),
                SubjectGroup: $("#addEditGroup").val(),
                TaughtBy: $("#addEditTaughtBy").val(),
                ReportOrder: $("#addEditOrder").val(),
                IsActive: $("#addEditIsActive").is(":checked")
            };
        }
    });
</script>
