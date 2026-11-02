{{--
    Peserta ujian: Generate Peserta (siswa aktif per tingkat, nomor otomatis), Atur Ruang (berurutan sesuai kapasitas),
    ubah ruang/kursi di grid lalu Simpan, cetak kartu (terpilih / per rombel / per ruang) & daftar hadir per ruang.
--}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="sg-filterbar">
            <div class="sg-field sg-field-grow" style="flex-basis: 480px;">
                <label for="filterExam">Ujian</label>
                <select class="form-control" id="filterExam">
                    @forelse ($exams as $e)
                        <option value="{{ $e['ExamId'] }}">{{ $e['Label'] }}</option>
                    @empty
                        <option value="">- belum ada ujian, buat di menu Kegiatan Ujian -</option>
                    @endforelse
                </select>
            </div>
        </div>
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_generate"><i class="fa fa-user-plus"></i> Generate Peserta</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_rooms"><i class="fa fa-building-o"></i> Atur Ruang Otomatis</button>
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_save" disabled><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_delete" disabled><i class="fa fa-trash-o"></i> Hapus</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
            </div>
            <div class="dp-toolbar-right">
                @if ($print)
                    <button type="button" class="dp-btn dp-btn-outline" id="btn_print"><i class="fa fa-print"></i> Cetak</button>
                @endif
                <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Ekspor Excel</button>
            </div>
        </div>
        <div class="box-body"><div id="datagrid"></div></div>
    </div>
</section>

<div class="modal fade sg-modal" id="modalGenerate" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-user-plus"></i></div>
                <div><div class="dpf-header-title">Generate Peserta</div><div class="dpf-header-sub">Siswa aktif di rombel tahun ajaran ujian yang belum terdaftar</div></div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Tingkat Kelas</div></div>
                    <div class="dpf-row-right">
                        @for ($g = 1; $g <= 6; $g++)
                            <label class="sg-check" style="margin-right: 12px;"><input type="checkbox" class="gen-grade" value="{{ $g }}" checked> Kelas {{ $g }}</label>
                        @endfor
                        <div class="dpf-hint">Nomor peserta: KODE-KELAS-URUT. Generate ulang hanya menambah siswa yang belum terdaftar.</div>
                    </div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_doGenerate"><i class="fa fa-user-plus"></i> Generate</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sg-modal" id="modalRooms" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-building-o"></i></div>
                <div><div class="dpf-header-title">Atur Ruang Otomatis</div><div class="dpf-header-sub">Peserta diurutkan nomor peserta, ruang diisi sesuai kapasitas</div></div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left"><div><div class="dpf-label">Ruangan</div><div class="dpf-sub">Diisi sesuai urutan</div></div></div>
                    <div class="dpf-row-right">
                        @forelse ($rooms as $r)
                            <div><label class="sg-check"><input type="checkbox" class="room-pick" value="{{ $r['RoomId'] }}" checked> {{ $r['RoomName'] }} ({{ $r['Capacity'] }} kursi)</label></div>
                        @empty
                            <div class="dpf-hint">Belum ada ruangan aktif. Tambahkan di menu Ruangan.</div>
                        @endforelse
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Mode</div></div>
                    <div class="dpf-row-right">
                        <label class="sg-check"><input type="checkbox" id="roomsOnlyEmpty" checked> Hanya peserta yang belum punya ruang</label>
                        <div class="dpf-hint">Hilangkan centang untuk mengatur ulang semua peserta.</div>
                    </div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_doRooms"><i class="fa fa-check"></i> Atur</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sg-modal" id="modalPrint" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-print"></i></div>
                <div><div class="dpf-header-title">Cetak</div><div class="dpf-header-sub">Kartu peserta (6 per halaman A4) atau daftar hadir per ruang</div></div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Peserta</div></div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="printScope">
                            <option value="all">Semua peserta</option>
                            <option value="selected">Peserta terpilih di grid</option>
                            <option value="classroom">Per rombel</option>
                            <option value="room">Per ruang</option>
                        </select>
                    </div>
                </div>
                <div class="dpf-row" id="printClassroomRow" style="display: none;">
                    <div class="dpf-row-left"><div class="dpf-label">Rombel</div></div>
                    <div class="dpf-row-right"><select class="form-control" id="printClassroom"></select></div>
                </div>
                <div class="dpf-row" id="printRoomRow" style="display: none;">
                    <div class="dpf-row-left"><div class="dpf-label">Ruang</div></div>
                    <div class="dpf-row-right"><select class="form-control" id="printRoom"></select></div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" id="btn_printAttendance"><i class="fa fa-list-ol"></i> Daftar Hadir</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_printCards"><i class="fa fa-id-card-o"></i> Kartu Peserta</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    var gCanEdit = @json($edit);
    var gRooms = @json($rooms);
    var gSheet;

    function examId() { return $("#filterExam").val(); }

    function load() {
        if (!examId()) { gSheet.load([]); return; }
        HMA.post("{{ url('/ExamParticipant/Search') }}", { examId: examId() }, function (result) {
            gSheet.load(result.data.participants);
            gSheet.grid.clearSelection();
            var $c = $("#printClassroom").empty(), $r = $("#printRoom").empty();
            $.each(result.data.classrooms, function (_, c) { $c.append(new Option(c.classroom_name, c.classroom_id)); });
            $.each(result.data.rooms, function (_, r) { $r.append(new Option(r.room_name, r.room_id)); });
        }, { successMessage: false });
    }

    function printUrl(base) {
        var scope = $("#printScope").val(), p = { examId: examId() };
        if (scope === "selected") {
            p.ids = gSheet.grid.getSelectedRowKeys().join(",");
            if (!p.ids) { showErrorMesgGrowl("Pilih peserta di grid terlebih dahulu."); return null; }
        }
        if (scope === "classroom") p.classroomId = $("#printClassroom").val();
        if (scope === "room") p.roomId = $("#printRoom").val();
        return base + "?" + $.param(p);
    }

    $(function () {
        gSheet = HMA.sheetGrid("#datagrid", {
            key: "ExamParticipantId",
            canEdit: gCanEdit,
            onDirty: function (n) { $("#btn_save").prop("disabled", n === 0); },
            gridOptions: {
                selection: { mode: "multiple", showCheckBoxesMode: "always", allowSelectAll: true },
                onSelectionChanged: function (e) { $("#btn_delete").prop("disabled", e.selectedRowKeys.length < 1); }
            },
            columns: [
                { dataField: "ParticipantNo", caption: "No Peserta", width: 140, sortOrder: "asc" },
                { dataField: "Nis", caption: "NIS", width: 100 },
                { dataField: "StudentName", caption: "Nama" },
                { dataField: "Gender", caption: "JK", alignment: "center", width: 55 },
                { dataField: "ClassroomName", caption: "Rombel", alignment: "center", width: 90 },
                { dataField: "HasPhoto", caption: "Foto", dataType: "boolean", alignment: "center", width: 70 },
                {
                    dataField: "RoomId", caption: "Ruang", width: 170, allowEditing: true,
                    lookup: { dataSource: gRooms, valueExpr: "RoomId", displayExpr: "RoomName", allowClearing: true }
                },
                { dataField: "SeatNo", caption: "Kursi", dataType: "number", alignment: "center", width: 80, allowEditing: true, editorOptions: { min: 1, showSpinButtons: false } }
            ]
        });

        load();
        HMA.guardFilter("#filterExam", function () { return gSheet; }, load);
        $("#btn_refresh").on("click", function () { HMA.confirmDiscard(gSheet, load); });
        $("#btn_save").on("click", function () {
            gSheet.commit();
            var rows = $.map(gSheet.dirtyRows(), function (r) { return { ExamParticipantId: r.ExamParticipantId, RoomId: r.RoomId || "", SeatNo: r.SeatNo || "" }; });
            HMA.post("{{ url('/ExamParticipant/Save') }}", { examId: examId(), rows: JSON.stringify(rows) }, load);
        });
        $("#btn_delete").on("click", function () {
            var ids = gSheet.grid.getSelectedRowKeys();
            showConfirmMesg("Hapus " + ids.length + " peserta terpilih?", function () {
                HMA.post("{{ url('/ExamParticipant/DeleteMultiple') }}", { examId: examId(), selectedIds: ids }).always(load);
            }, "Konfirmasi");
        });

        $("#btn_generate").on("click", function () { if (examId()) HMA.confirmDiscard(gSheet, function () { $("#modalGenerate").modal("show"); }); });
        $("#btn_doGenerate").on("click", function () {
            var grades = $(".gen-grade:checked").map(function () { return this.value; }).get();
            HMA.post("{{ url('/ExamParticipant/Generate') }}", { examId: examId(), gradeLevels: grades }, function () { $("#modalGenerate").modal("hide"); load(); });
        });
        $("#btn_rooms").on("click", function () { if (examId()) HMA.confirmDiscard(gSheet, function () { $("#modalRooms").modal("show"); }); });
        $("#btn_doRooms").on("click", function () {
            var rooms = $(".room-pick:checked").map(function () { return this.value; }).get();
            HMA.post("{{ url('/ExamParticipant/AssignRooms') }}", { examId: examId(), roomIds: rooms, onlyEmpty: $("#roomsOnlyEmpty").is(":checked") ? 1 : 0 },
                function () { $("#modalRooms").modal("hide"); load(); });
        });

        $("#btn_print").on("click", function () { if (examId()) $("#modalPrint").modal("show"); });
        $("#printScope").on("change", function () {
            $("#printClassroomRow").toggle(this.value === "classroom");
            $("#printRoomRow").toggle(this.value === "room");
        });
        $("#btn_printCards").on("click", function () { var u = printUrl("{{ url('/ExamParticipant/PrintCards') }}"); if (u) window.open(u, "_blank"); });
        $("#btn_printAttendance").on("click", function () { var u = printUrl("{{ url('/ExamParticipant/PrintAttendance') }}"); if (u) window.open(u, "_blank"); });
        $("#btn_export").on("click", function () { if (examId()) window.location.href = "{{ url('/ExamParticipant/DownloadExcel') }}?examId=" + examId(); });
    });
</script>
@endsection
