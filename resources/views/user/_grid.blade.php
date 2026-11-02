<div class="box-body">
    <div id="datagrid"></div>
</div>

<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gGrid = null;

    $(document).ready(function () {
        gGrid = HMA.createGrid("#datagrid", {
            key: "UserId",
            url: "{{ url('/User/Search') }}",
            gridOptions: {
                onRowDblClick: function (e) { if (gCanEdit) onEditPrepare(e.data.UserId); }
            },
            onSelectionChanged: function (rows) {
                $("#btn_edit").prop("disabled", rows.length !== 1);
                $("#btn_delete").prop("disabled", rows.length < 1);
                // User Active Directory mengganti password lewat Windows.
                $("#btn_resetPassword").prop("disabled", rows.length !== 1 || rows[0].IsAdUser);
            },
            columns: [
                HMA.col.rowNumber(),
                { dataField: "Username", caption: "Username", fixed: true, fixedPosition: "left" },
                { dataField: "FullName", caption: "Nama Lengkap" },
                { dataField: "EmployeeNo", caption: "NIP / No. Pegawai" },
                { dataField: "RoleName", caption: "Peran" },
                { dataField: "SupervisorName", caption: "Atasan" },
                {
                    dataField: "IsAdUser", caption: "Jenis Login", alignment: "center", width: 130,
                    calculateCellValue: function (row) { return row.IsAdUser ? "Active Directory" : "Lokal"; },
                    cellTemplate: function (container, options) {
                        $("<span>").addClass("sg-badge" + (options.data.IsAdUser ? "" : " gray"))
                            .text(options.data.IsAdUser ? "Active Directory" : "Lokal")
                            .appendTo(container);
                    }
                },
                { dataField: "Email", caption: "Email", visible: false },
                { dataField: "Phone", caption: "No HP", visible: false },
                HMA.col.date("LastLoginDate", "Login Terakhir", true),
                HMA.col.status()
            ].concat(HMA.col.audit())
        });
    });
</script>
