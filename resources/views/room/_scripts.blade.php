<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "Ruangan",
        key: "RoomId",
        keyParam: "roomId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Room/Search') }}",
            getByKey: "{{ url('/Room/GetByKey') }}",
            save: "{{ url('/Room/AddEdit') }}",
            remove: "{{ url('/Room/DeleteMultiple') }}",
            exportExcel: "{{ url('/Room/DownloadExcel') }}"
        },
        columns: [
            { dataField: "RoomCode", caption: "Kode", width: 90 },
            { dataField: "RoomName", caption: "Nama Ruangan" },
            { dataField: "Capacity", caption: "Kapasitas", dataType: "number", alignment: "center", width: 100 },
            HMA.col.status()
        ],
        focusAdd: "#addEditCode",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditCode").val("").prop("disabled", false);
            $("#addEditName").val("");
            $("#addEditCapacity").val(28);
            $("#addEditIsActive").prop("checked", true);
        },
        fillForm: function (data) {
            $("#addEditId").val(data.RoomId);
            $("#addEditCode").val(data.RoomCode).prop("disabled", true);
            $("#addEditName").val(data.RoomName);
            $("#addEditCapacity").val(data.Capacity);
            $("#addEditIsActive").prop("checked", data.IsActive);
        },
        collectForm: function () {
            return {
                RoomId: $("#addEditId").val(),
                RoomCode: $("#addEditCode").val(),
                RoomName: $("#addEditName").val(),
                Capacity: $("#addEditCapacity").val(),
                IsActive: $("#addEditIsActive").is(":checked")
            };
        }
    });
</script>
