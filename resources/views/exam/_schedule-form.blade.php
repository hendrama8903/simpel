{{-- Popup jadwal ujian per tingkat (diisi di grid, Simpan mengganti seluruh jadwal ujian tsb). --}}
<div class="modal fade sg-modal" id="modalSchedule" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" style="width: 92%; max-width: 1000px;">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-clock-o"></i></div>
                <div>
                    <div class="dpf-header-title">Jadwal Ujian</div>
                    <div class="dpf-header-sub" id="scheduleSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body" style="padding-top: 12px;">
                @if ($edit)
                    <div style="margin-bottom: 8px;">
                        <button type="button" class="dp-btn dp-btn-outline" id="btn_schAdd"><i class="fa fa-plus"></i> Tambah Baris</button>
                        <button type="button" class="dp-btn dp-btn-outline" id="btn_schAddGrades"><i class="fa fa-clone"></i> Salin Baris Terpilih ke Semua Kelas</button>
                        <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_schDelete"><i class="fa fa-trash-o"></i> Hapus Baris Terpilih</button>
                    </div>
                @endif
                <div id="scheduleGrid"></div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Tutup</button>
                @if ($edit)
                    <button type="button" class="dpf-btn dpf-btn-primary" id="btn_schSave"><i class="fa fa-save"></i> Simpan Jadwal</button>
                @endif
            </div>
        </div>
    </div>
</div>
