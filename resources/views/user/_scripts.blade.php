<script type="text/javascript">
    var gScreenMode = null;

    $(document).ready(function () {
        HMA.select2("#addEditRole", "{{ url('/Lookup/Role') }}", "Pilih peran", "#modalAddEdit");
        HMA.select2("#addEditSupervisor", "{{ url('/Lookup/UserList') }}", "Pilih atasan", "#modalAddEdit");

        $("#btn_add").on("click", onAddPrepare);
        $("#btn_edit").on("click", function () {
            var rows = gGrid.getSelectedRowsData();
            if (rows.length === 1) onEditPrepare(rows[0].UserId);
        });
        $("#btn_delete").on("click", function () {
            onDeletePrepare(gGrid.getSelectedRowKeys());
        });
        $("#btn_resetPassword").on("click", function () {
            var rows = gGrid.getSelectedRowsData();
            if (rows.length === 1 && !rows[0].IsAdUser) onResetPasswordPrepare(rows[0]);
        });
        $("#btn_refresh").on("click", onSearch);
        $("#btn_export").on("click", function () {
            window.location.href = "{{ url('/User/DownloadExcel') }}";
        });
        $("#btn_save").on("click", function () {
            showConfirmMesg("Simpan data ini?", saveData, "Konfirmasi");
        });
        $("#btn_resetPasswordSave").on("click", saveResetPassword);

        $("input[name='addEditLoginType']").on("change", applyLoginType);
    });

    function onSearch() {
        gGrid.clearSelection();
        gGrid.refresh();
    }

    function isAdLogin() {
        return $("input[name='addEditLoginType']:checked").val() === "AD";
    }

    // Password hanya untuk akun lokal; saat edit boleh kosong (= tidak diubah).
    function applyLoginType() {
        var local = !isAdLogin();
        $("#rowPassword").toggle(local);
        $("#passwordRequired").toggle(gScreenMode === "ADD");
        $("#passwordHint").text(gScreenMode === "ADD"
            ? "Wajib untuk akun lokal."
            : "Kosongkan bila password tidak diubah.");
    }

    function onAddPrepare() {
        gScreenMode = "ADD";
        $("#modal-title").text("Tambah Pengguna");
        $("#modal-subtitle").text("Daftarkan pengguna baru");
        $("#addEditId").val("");
        $("#addEditUsername").val("").prop("disabled", false);
        $("#addEditPassword, #addEditFullName, #addEditEmployeeNo, #addEditEmail, #addEditPhone").val("");
        $("input[name='addEditLoginType'][value='LOCAL']").prop("checked", true);
        HMA.setSelect2Value("#addEditRole", null);
        HMA.setSelect2Value("#addEditSupervisor", null);
        $("#addEditIsActive").prop("checked", true);
        applyLoginType();

        $("#modalAddEdit").modal("show").one("shown.bs.modal", function () { $("#addEditUsername").focus(); });
    }

    function onEditPrepare(userId) {
        HMA.post("{{ url('/User/GetByKey') }}", { userId: userId }, function (result) {
            var data = result.data;
            gScreenMode = "EDIT";
            $("#modal-title").text("Ubah Pengguna");
            $("#modal-subtitle").text("Perbarui pengguna " + data.Username);
            $("#addEditId").val(data.UserId);
            $("#addEditUsername").val(data.Username).prop("disabled", true);
            $("#addEditPassword").val("");
            $("#addEditFullName").val(data.FullName);
            $("#addEditEmployeeNo").val(data.EmployeeNo);
            $("#addEditEmail").val(data.Email);
            $("#addEditPhone").val(data.Phone);
            $("input[name='addEditLoginType'][value='" + (data.IsAdUser ? "AD" : "LOCAL") + "']").prop("checked", true);
            HMA.setSelect2Value("#addEditRole", data.RoleId, data.RoleName);
            HMA.setSelect2Value("#addEditSupervisor", data.SupervisorUserId, data.SupervisorName);
            $("#addEditIsActive").prop("checked", data.IsActive);
            applyLoginType();

            $("#modalAddEdit").modal("show").one("shown.bs.modal", function () { $("#addEditFullName").focus(); });
        }, { successMessage: false });
    }

    function saveData() {
        HMA.post("{{ url('/User/AddEdit') }}", {
            screenMode: gScreenMode,
            UserId: $("#addEditId").val(),
            Username: $("#addEditUsername").val(),
            password: isAdLogin() ? "" : $("#addEditPassword").val(),
            FullName: $("#addEditFullName").val(),
            EmployeeNo: $("#addEditEmployeeNo").val(),
            Email: $("#addEditEmail").val(),
            Phone: $("#addEditPhone").val(),
            RoleId: $("#addEditRole").val(),
            SupervisorUserId: $("#addEditSupervisor").val(),
            IsAdUser: isAdLogin(),
            IsActive: $("#addEditIsActive").is(":checked")
        }, function () {
            $("#modalAddEdit").modal("hide");
            onSearch();
        });
    }

    function onDeletePrepare(ids) {
        if (!ids || ids.length < 1) return;
        showConfirmMesg("Hapus " + ids.length + " pengguna yang dipilih?", function () {
            // Refresh juga saat sebagian gagal, karena record lain mungkin sudah terhapus.
            HMA.post("{{ url('/User/DeleteMultiple') }}", { selectedIds: ids }).always(onSearch);
        }, "Konfirmasi");
    }

    function onResetPasswordPrepare(row) {
        $("#resetPasswordId").val(row.UserId);
        $("#resetPasswordUser").text(row.Username + " - " + row.FullName);
        $("#resetPasswordNew, #resetPasswordConfirm").val("");
        $("#modalResetPassword").modal("show").one("shown.bs.modal", function () { $("#resetPasswordNew").focus(); });
    }

    function saveResetPassword() {
        if ($("#resetPasswordNew").val() !== $("#resetPasswordConfirm").val()) {
            showErrorMesgGrowl("Password baru dan konfirmasinya tidak sama.");
            return;
        }
        HMA.post("{{ url('/User/ResetPassword') }}", {
            userId: $("#resetPasswordId").val(),
            newPassword: $("#resetPasswordNew").val()
        }, function () {
            $("#modalResetPassword").modal("hide");
        });
    }
</script>
