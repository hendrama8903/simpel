<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "Ekstrakurikuler",
        key: "ExtracurricularId",
        keyParam: "extracurricularId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Extracurricular/Search') }}",
            getByKey: "{{ url('/Extracurricular/GetByKey') }}",
            save: "{{ url('/Extracurricular/AddEdit') }}",
            remove: "{{ url('/Extracurricular/DeleteMultiple') }}",
            exportExcel: "{{ url('/Extracurricular/DownloadExcel') }}"
        },
        columns: [
            { dataField: "ExtracurricularCode", caption: "Kode", width: 90 },
            { dataField: "ExtracurricularName", caption: "Nama Ekstrakurikuler" },
            { dataField: "CoachName", caption: "Pembina" },
            HMA.col.status("IsMandatory", "Wajib", "Wajib", "Pilihan"),
            HMA.col.status()
        ],
        focusAdd: "#addEditCode",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditCode").val("").prop("disabled", false);
            $("#addEditName").val("");
            $("#addEditCoach").val("");
            $("#addEditIsMandatory").prop("checked", false);
            $("#addEditIsActive").prop("checked", true);
        },
        fillForm: function (data) {
            $("#addEditId").val(data.ExtracurricularId);
            $("#addEditCode").val(data.ExtracurricularCode).prop("disabled", true);
            $("#addEditName").val(data.ExtracurricularName);
            $("#addEditCoach").val(data.CoachName);
            $("#addEditIsMandatory").prop("checked", data.IsMandatory);
            $("#addEditIsActive").prop("checked", data.IsActive);
        },
        collectForm: function () {
            return {
                ExtracurricularId: $("#addEditId").val(),
                ExtracurricularCode: $("#addEditCode").val(),
                ExtracurricularName: $("#addEditName").val(),
                CoachName: $("#addEditCoach").val(),
                IsMandatory: $("#addEditIsMandatory").is(":checked"),
                IsActive: $("#addEditIsActive").is(":checked")
            };
        }
    });
</script>
