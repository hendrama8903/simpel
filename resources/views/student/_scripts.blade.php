<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    window.onExcelUploaded = function () { gPage.search(); };
    var gCanEdit = @json($edit);

    function showPhoto(studentId, hasPhoto) {
        $("#photoImg").toggle(hasPhoto).attr("src", hasPhoto ? "{{ url('/Student/Photo') }}?id=" + studentId + "&v=" + Date.now() : "");
        $("#photoEmpty").toggle(!hasPhoto);
    }
    var gPage = HMA.crudPage({
        entity: "Siswa",
        key: "StudentId",
        keyParam: "studentId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/Student/Search') }}",
            getByKey: "{{ url('/Student/GetByKey') }}",
            save: "{{ url('/Student/AddEdit') }}",
            remove: "{{ url('/Student/DeleteMultiple') }}",
            exportExcel: function () { return "{{ url('/Student/DownloadExcel') }}?" + $.param({ status: $("#filterStatus").val(), classroomId: $("#filterClassroom").val() }); }
        },
        params: function () { return { status: $("#filterStatus").val(), classroomId: $("#filterClassroom").val() }; },
        columns: [
            { dataField: "Nis", caption: "NIS", fixed: true, fixedPosition: "left" },
            { dataField: "Nisn", caption: "NISN" },
            { dataField: "StudentName", caption: "Nama" },
            { dataField: "Gender", caption: "JK", alignment: "center", width: 60 },
            { dataField: "BirthPlace", caption: "Tempat Lahir", visible: false },
            HMA.col.date("BirthDate", "Tanggal Lahir"),
            { dataField: "Religion", caption: "Agama", visible: false },
            { dataField: "ClassroomName", caption: "Rombel", alignment: "center" },
            { dataField: "MotherName", caption: "Nama Ibu", visible: false },
            { dataField: "ParentPhone", caption: "No HP Ortu", visible: false },
            { dataField: "HasPhoto", caption: "Foto", dataType: "boolean", alignment: "center", width: 70 },
            { dataField: "StatusName", caption: "Status", alignment: "center", cellTemplate: function (c, o) { $("<span>").addClass("sg-badge " + ({ AKTIF: "green", MUTASI: "amber", LULUS: "gray", KELUAR: "red" })[o.data.Status]).text(o.value).appendTo(c); } }
        ],
        focusAdd: "#addEditNis",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditNis").val("");
            $("#addEditNisn").val("");
            $("#addEditNik").val("");
            $("#addEditName").val("");
            $("#addEditGender").val("L");
            $("#addEditBirthPlace").val("");
            $("#addEditBirthDate").val("");
            $("#addEditReligion").val("");
            $("#addEditAddress").val("");
            $("#addEditFather").val("");
            $("#addEditMother").val("");
            $("#addEditGuardian").val("");
            $("#addEditParentPhone").val("");
            $("#addEditEntryType").val("BARU");
            $("#addEditEntryDate").val("");
            $("#addEditEntryGrade").val("1");
            $("#addEditPrevSchool").val("");
            $("#photoBox").hide();
        },
        fillForm: function (data) {
            $("#addEditId").val(data.StudentId);
            $("#addEditNis").val(data.Nis);
            $("#addEditNisn").val(data.Nisn);
            $("#addEditNik").val(data.Nik);
            $("#addEditName").val(data.StudentName);
            $("#addEditGender").val(data.Gender);
            $("#addEditBirthPlace").val(data.BirthPlace);
            $("#addEditBirthDate").val(data.BirthDate ? String(data.BirthDate).substr(0, 10) : "");
            $("#addEditReligion").val(data.Religion);
            $("#addEditAddress").val(data.Address);
            $("#addEditFather").val(data.FatherName);
            $("#addEditMother").val(data.MotherName);
            $("#addEditGuardian").val(data.GuardianName);
            $("#addEditParentPhone").val(data.ParentPhone);
            $("#addEditEntryType").val(data.EntryType);
            $("#addEditEntryDate").val(data.EntryDate ? String(data.EntryDate).substr(0, 10) : "");
            $("#addEditEntryGrade").val(data.EntryGrade);
            $("#addEditPrevSchool").val(data.PreviousSchool);
            $("#photoBox").show();
            showPhoto(data.StudentId, data.HasPhoto);
        },
        collectForm: function () {
            return {
                StudentId: $("#addEditId").val(),
                Nis: $("#addEditNis").val(),
                Nisn: $("#addEditNisn").val(),
                Nik: $("#addEditNik").val(),
                StudentName: $("#addEditName").val(),
                Gender: $("#addEditGender").val(),
                BirthPlace: $("#addEditBirthPlace").val(),
                BirthDate: $("#addEditBirthDate").val(),
                Religion: $("#addEditReligion").val(),
                Address: $("#addEditAddress").val(),
                FatherName: $("#addEditFather").val(),
                MotherName: $("#addEditMother").val(),
                GuardianName: $("#addEditGuardian").val(),
                ParentPhone: $("#addEditParentPhone").val(),
                EntryType: $("#addEditEntryType").val(),
                EntryDate: $("#addEditEntryDate").val(),
                EntryGrade: $("#addEditEntryGrade").val(),
                PreviousSchool: $("#addEditPrevSchool").val()
            };
        }
    });

    $(function () {
        $("#filterStatus, #filterClassroom").on("change", gPage.search);

        // Foto hanya bisa diganti untuk siswa yang sudah tersimpan (mode Ubah).
        $("#btn_photo").on("click", function () { $("#photoFile").val("").trigger("click"); });
        $("#photoFile").on("change", function () {
            var file = this.files[0], studentId = $("#addEditId").val();
            if (!file || !studentId) return;
            var form = new FormData();
            form.append("file", file);
            form.append("studentId", studentId);
            popUpProgressShow();
            $.ajax({ type: "POST", url: "{{ url('/Student/UploadPhoto') }}", data: form, processData: false, contentType: false, dataType: "json" })
                .done(function (result) {
                    popUpProgressHide();
                    if (!result.status) { showErrorMesgGrowl(HMA.formatMessage(result.message)); return; }
                    showSuccessMesgGrowl(HMA.formatMessage(result.message));
                    showPhoto(studentId, true);
                    gPage.search();
                })
                .fail(popUpProgressHide);
        });
    });
</script>
