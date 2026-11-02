<script type="text/javascript">
    // Toolbar, grid, popup, simpan & hapus ditangani HMA.crudPage (public/js/hma.js).
    var gPage = HMA.crudPage({
        entity: "Tahun Ajaran",
        key: "AcademicYearId",
        keyParam: "academicYearId",
        canEdit: @json($edit),
        canDelete: @json($delete),
        urls: {
            search: "{{ url('/AcademicYear/Search') }}",
            getByKey: "{{ url('/AcademicYear/GetByKey') }}",
            save: "{{ url('/AcademicYear/AddEdit') }}",
            remove: "{{ url('/AcademicYear/DeleteMultiple') }}",
            exportExcel: "{{ url('/AcademicYear/DownloadExcel') }}"
        },
        columns: [
            { dataField: "YearName", caption: "Tahun Ajaran", width: 130 },
            HMA.col.date("Semester1Start", "Ganjil Mulai"),
            HMA.col.date("Semester1End", "Ganjil Selesai"),
            HMA.col.date("Semester2Start", "Genap Mulai"),
            HMA.col.date("Semester2End", "Genap Selesai"),
            { dataField: "ActiveSemesterName", caption: "Semester Aktif", alignment: "center", cellTemplate: function (c, o) { if (o.value) $("<span class='sg-badge green'>").text(o.value).appendTo(c); } }
        ],
        focusAdd: "#addEditName",
        focusEdit: "#addEditName",
        resetForm: function () {
            $("#addEditId").val("");
            $("#addEditName").val("");
            $("#addEditS1Start").val("");
            $("#addEditS1End").val("");
            $("#addEditS1Report").val("");
            $("#addEditS2Start").val("");
            $("#addEditS2End").val("");
            $("#addEditS2Report").val("");
            $("#addEditActive").val("0");
        },
        fillForm: function (data) {
            $("#addEditId").val(data.AcademicYearId);
            $("#addEditName").val(data.YearName);
            $("#addEditS1Start").val(data.Semester1Start ? String(data.Semester1Start).substr(0, 10) : "");
            $("#addEditS1End").val(data.Semester1End ? String(data.Semester1End).substr(0, 10) : "");
            $("#addEditS1Report").val(data.Semester1ReportDate ? String(data.Semester1ReportDate).substr(0, 10) : "");
            $("#addEditS2Start").val(data.Semester2Start ? String(data.Semester2Start).substr(0, 10) : "");
            $("#addEditS2End").val(data.Semester2End ? String(data.Semester2End).substr(0, 10) : "");
            $("#addEditS2Report").val(data.Semester2ReportDate ? String(data.Semester2ReportDate).substr(0, 10) : "");
            $("#addEditActive").val(data.ActiveSemester);
        },
        collectForm: function () {
            return {
                AcademicYearId: $("#addEditId").val(),
                YearName: $("#addEditName").val(),
                Semester1Start: $("#addEditS1Start").val(),
                Semester1End: $("#addEditS1End").val(),
                Semester1ReportDate: $("#addEditS1Report").val(),
                Semester2Start: $("#addEditS2Start").val(),
                Semester2End: $("#addEditS2End").val(),
                Semester2ReportDate: $("#addEditS2Report").val(),
                ActiveSemester: $("#addEditActive").val()
            };
        }
    });

    // Isi otomatis tanggal semester dari tahun ajaran (Juli-Desember & Januari-Juni) bila masih kosong.
    $(function () {
        $("#addEditName").on("change", function () {
            var m = /^(\d{4})\/(\d{4})$/.exec($.trim(this.value));
            if (!m || $("#addEditS1Start").val()) return;
            $("#addEditS1Start").val(m[1] + "-07-01");
            $("#addEditS1End").val(m[1] + "-12-31");
            $("#addEditS2Start").val(m[2] + "-01-01");
            $("#addEditS2End").val(m[2] + "-06-30");
        });
    });
</script>
