{{--
    Presensi siswa. Tab Harian: pilih rombel & tanggal, isi status H/S/I/A per siswa lalu Simpan.
    Tab Rekap: matriks satu bulan (tanggal x siswa) + jumlah, bisa di-export ke Excel.
--}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="sg-filterbar">
            <div class="sg-field">
                <label for="filterSemester">Semester</label>
                <select class="form-control" id="filterSemester">
                    @foreach ($semesters as $s)
                        <option value="{{ $s->SemesterId }}" @selected($s->IsActive)>{{ $s->Label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sg-field">
                <label for="filterClassroom">Rombel</label>
                <select class="form-control" id="filterClassroom"></select>
            </div>
        </div>
    </div>

    <div class="sg-tabs" style="margin-top: 14px;">
        <button type="button" class="sg-tab active" data-pane="paneDaily"><i class="fa fa-check-square-o"></i> Harian</button>
        <button type="button" class="sg-tab" data-pane="paneRecap"><i class="fa fa-table"></i> Rekap Bulanan</button>
    </div>

    <div class="dp-card sg-pane" id="paneDaily">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                <input type="date" class="form-control" id="filterDate" style="width: 160px;">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_save" disabled><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_allPresent"><i class="fa fa-check"></i> Isi Kosong = Hadir</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
            </div>
            <div class="dp-toolbar-right"><span id="dailySummary" class="sg-info" style="padding: 0;"></span></div>
        </div>
        <div class="box-body"><div id="datagrid"></div></div>
    </div>

    <div class="dp-card sg-pane" id="paneRecap" style="display: none;">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                <input type="month" class="form-control" id="filterMonth" style="width: 170px;">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_recapRefresh"><i class="fa fa-refresh"></i> Tampilkan</button>
            </div>
            <div class="dp-toolbar-right">
                <button type="button" class="dp-btn dp-btn-outline" id="btn_recapExport"><i class="fa fa-download"></i> Ekspor Excel</button>
            </div>
        </div>
        <div class="box-body"><div id="recapGrid"></div></div>
    </div>
</section>
@endsection

@section('scripts')
<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gStatuses = $.map(@json($statuses), function (name, code) { return { Code: code, Name: code + " - " + name }; });
    var gSheet, gRecapGrid, gLoaded = null, gRange = null;

    function filters() {
        return { semesterId: $("#filterSemester").val(), classroomId: $("#filterClassroom").val() };
    }

    function loadClassrooms(done) {
        HMA.post("{{ url('/Attendance/Classrooms') }}", { semesterId: $("#filterSemester").val() }, function (result) {
            var d = result.data, $c = $("#filterClassroom").empty();
            $.each(d.classrooms || [], function (_, c) { $c.append(new Option(c.ClassroomName, c.ClassroomId)); });
            if (!$c.children().length) $c.append(new Option("- tidak ada rombel -", ""));
            $c.data("prev", $c.val());
            gRange = d.startDate ? { start: d.startDate, end: d.endDate } : null;
            // Tanggal default: hari ini kalau masih di dalam semester, selain itu tanggal terakhir semester.
            var today = new Date().toISOString().substr(0, 10);
            if (gRange) {
                var def = today < gRange.start ? gRange.start : (today > gRange.end ? gRange.end : today);
                $("#filterDate").attr({ min: gRange.start, max: gRange.end < today ? gRange.end : today }).val(def).data("prev", def);
                $("#filterMonth").val(def.substr(0, 7));
            }
            if (done) done();
        }, { successMessage: false, block: false });
    }

    function load() {
        var params = $.extend(filters(), { date: $("#filterDate").val() });
        if (!params.classroomId || !params.date) { gSheet.load([]); gLoaded = null; summary(); return; }
        HMA.post("{{ url('/Attendance/Sheet') }}", params, function (result) {
            gLoaded = params;
            gSheet.load(result.data);
            summary();
        }, { successMessage: false });
    }

    function summary() {
        var c = { H: 0, S: 0, I: 0, A: 0, kosong: 0 };
        $.each(gSheet.rows, function (_, r) { if (r.Status) c[r.Status]++; else c.kosong++; });
        $("#dailySummary").text(gSheet.rows.length ? ("Hadir " + c.H + " · Sakit " + c.S + " · Izin " + c.I + " · Alpa " + c.A + " · Belum diisi " + c.kosong) : "");
    }

    function save() {
        gSheet.commit();
        var rows = $.map(gSheet.dirtyRows(), function (r) { return { StudentId: r.StudentId, Status: r.Status || "", Note: r.Note || "" }; });
        HMA.post("{{ url('/Attendance/Save') }}", $.extend({ rows: JSON.stringify(rows) }, gLoaded), function () { gSheet.clearDirty(); });
    }

    function loadRecap() {
        var params = $.extend(filters(), { month: $("#filterMonth").val() });
        if (!params.classroomId || !params.month) return;
        HMA.post("{{ url('/Attendance/Recap') }}", params, function (result) {
            var d = result.data, columns = [
                HMA.col.rowNumber(),
                { dataField: "Nis", caption: "NIS", fixed: true, fixedPosition: "left" },
                { dataField: "StudentName", caption: "Nama", fixed: true, fixedPosition: "left" }
            ];
            for (var day = 1; day <= d.days; day++) {
                columns.push({
                    dataField: "D" + day, caption: String(day), alignment: "center", width: 34, allowSorting: false, allowHeaderFiltering: false,
                    cellTemplate: function (c, o) { if (o.value) $("<span>").addClass("sg-att " + o.value).text(o.value).appendTo(c); }
                });
            }
            $.each(["H", "S", "I", "A"], function (_, k) { columns.push({ dataField: k, caption: k, dataType: "number", alignment: "center", width: 45 }); });
            columns.push({ dataField: "Percent", caption: "% Hadir", dataType: "number", alignment: "center", width: 75 });
            gRecapGrid.option({ columns: $.map(columns, HMA.relaxWidth), dataSource: d.rows });
        }, { successMessage: false });
    }

    $(function () {
        gSheet = HMA.sheetGrid("#datagrid", {
            key: "StudentId",
            canEdit: gCanEdit,
            onDirty: function (n) { $("#btn_save").prop("disabled", n === 0); },
            onChanged: summary,
            columns: [
                HMA.col.rowNumber(),
                { dataField: "Nis", caption: "NIS", width: 110 },
                { dataField: "StudentName", caption: "Nama" },
                { dataField: "Gender", caption: "JK", alignment: "center", width: 55 },
                {
                    dataField: "Status", caption: "Status", allowEditing: true, width: 160,
                    lookup: { dataSource: gStatuses, valueExpr: "Code", displayExpr: "Name", allowClearing: true },
                    cellTemplate: function (c, o) {
                        if (o.value) $("<span>").addClass("sg-att " + o.value).text(o.displayValue).appendTo(c);
                        else c.append($("<span class='text-muted'>").text("belum diisi"));
                    }
                },
                { dataField: "Note", caption: "Keterangan", allowEditing: true, editorOptions: { maxLength: 255 } }
            ]
        });

        gRecapGrid = $("#recapGrid").addClass("sg-grid").dxDataGrid($.extend(HMA.filterDefaults(), {
            dataSource: [], keyExpr: "StudentId", showBorders: true, showRowLines: true, showColumnLines: true,
            columnAutoWidth: true, allowColumnResizing: true, columnFixing: { enabled: true },
            height: HMA.gridHeight("#recapGrid"), paging: { enabled: false }, noDataText: "Pilih bulan lalu klik Tampilkan."
        })).dxDataGrid("instance");

        loadClassrooms(load);
        HMA.guardFilter("#filterSemester", function () { return gSheet; }, function () { loadClassrooms(load); });
        HMA.guardFilter("#filterClassroom, #filterDate", function () { return gSheet; }, function () {
            load();
            if ($("#paneRecap").is(":visible")) loadRecap();
        });
        $("#btn_refresh").on("click", function () { HMA.confirmDiscard(gSheet, load); });
        $("#btn_save").on("click", function () { showConfirmMesg("Simpan presensi " + gSheet.dirtyCount() + " siswa?", save, "Konfirmasi"); });
        $("#btn_allPresent").on("click", function () {
            $.each(gSheet.rows, function (_, r) { if (!r.Status) { r.Status = "H"; gSheet.markDirty(r); } });
            gSheet.grid.repaint();
            summary();
        });

        $(".sg-tab").on("click", function () {
            $(".sg-tab").removeClass("active");
            $(this).addClass("active");
            $(".sg-pane").hide();
            $("#" + $(this).data("pane")).show();
            if ($(this).data("pane") === "paneRecap") { gRecapGrid.option("height", HMA.gridHeight("#recapGrid")); loadRecap(); }
            else gSheet.grid.option("height", HMA.gridHeight("#datagrid"));
        });
        $("#btn_recapRefresh").on("click", loadRecap);
        $("#filterMonth").on("change", loadRecap);
        $("#btn_recapExport").on("click", function () {
            window.location.href = "{{ url('/Attendance/DownloadRecap') }}?" + $.param($.extend(filters(), { month: $("#filterMonth").val() }));
        });
    });
</script>
@endsection
