<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    window.onExcelUploaded = function () { gPage.search(); };
    var gPage = HMA.crudPage({
        entity: "Guru",
        key: "TeacherId",
        keyParam: "teacherId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Teacher/Search') }}",
            getByKey: "{{ url('/Teacher/GetByKey') }}",
            save: "{{ url('/Teacher/AddEdit') }}",
            remove: "{{ url('/Teacher/DeleteMultiple') }}",
            exportExcel: "{{ url('/Teacher/DownloadExcel') }}"
        },
        columns: [
            { dataField: "Nip", caption: "NIP" },
            { dataField: "Nuptk", caption: "NUPTK", visible: false },
            { dataField: "TeacherName", caption: "Nama" },
            { dataField: "Gender", caption: "JK", alignment: "center", width: 60 },
            { dataField: "PositionName", caption: "Jabatan" },
            { dataField: "EmploymentStatus", caption: "Status Kepegawaian" },
            { dataField: "Phone", caption: "No HP", visible: false },
            { dataField: "Email", caption: "Email", visible: false },
            { dataField: "Username", caption: "Akun Login" },
            HMA.col.status()
        ],
        focusAdd: "#addEditName",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditName").val("");
            $("#addEditNip").val("");
            $("#addEditNuptk").val("");
            $("#addEditGender").val("L");
            $("#addEditBirthPlace").val("");
            $("#addEditBirthDate").val("");
            $("#addEditPosition").val("GURU_KELAS");
            $("#addEditEmployment").val("");
            $("#addEditPhone").val("");
            $("#addEditEmail").val("");
            $("#addEditAddress").val("");
            HMA.setSelect2Value("#addEditUser", null);
            $("#addEditIsActive").prop("checked", true);
        },
        fillForm: function (data) {
            $("#addEditId").val(data.TeacherId);
            $("#addEditName").val(data.TeacherName);
            $("#addEditNip").val(data.Nip);
            $("#addEditNuptk").val(data.Nuptk);
            $("#addEditGender").val(data.Gender);
            $("#addEditBirthPlace").val(data.BirthPlace);
            $("#addEditBirthDate").val(data.BirthDate ? String(data.BirthDate).substr(0, 10) : "");
            $("#addEditPosition").val(data.Position);
            $("#addEditEmployment").val(data.EmploymentStatus);
            $("#addEditPhone").val(data.Phone);
            $("#addEditEmail").val(data.Email);
            $("#addEditAddress").val(data.Address);
            HMA.setSelect2Value("#addEditUser", data.UserId, data.UserText);
            $("#addEditIsActive").prop("checked", data.IsActive);
        },
        collectForm: function () {
            return {
                TeacherId: $("#addEditId").val(),
                TeacherName: $("#addEditName").val(),
                Nip: $("#addEditNip").val(),
                Nuptk: $("#addEditNuptk").val(),
                Gender: $("#addEditGender").val(),
                BirthPlace: $("#addEditBirthPlace").val(),
                BirthDate: $("#addEditBirthDate").val(),
                Position: $("#addEditPosition").val(),
                EmploymentStatus: $("#addEditEmployment").val(),
                Phone: $("#addEditPhone").val(),
                Email: $("#addEditEmail").val(),
                Address: $("#addEditAddress").val(),
                UserId: $("#addEditUser").val(),
                IsActive: $("#addEditIsActive").is(":checked")
            };
        }
    });

    $(function () {
        HMA.select2("#addEditUser", "{{ url('/Lookup/UserList') }}", "Pilih user", "#modalAddEdit");

        // Buat akun login untuk guru terpilih yang belum punya akun.
        gPage.grid.on("selectionChanged", function (e) { $("#btn_account").prop("disabled", e.selectedRowKeys.length < 1); });
        $("#btn_account").on("click", function () {
            var ids = gPage.grid.getSelectedRowKeys();
            showConfirmMesg("Buat akun login untuk " + ids.length + " guru terpilih?", function () {
                HMA.post("{{ url('/Teacher/CreateAccounts') }}", { selectedIds: ids }, gPage.search);
            }, "Konfirmasi");
        });
    });
</script>
