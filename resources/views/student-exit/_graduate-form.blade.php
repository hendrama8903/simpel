<div class="modal fade sg-modal" id="modalGraduate" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-graduation-cap"></i></div>
                <div>
                    <div class="dpf-header-title">Luluskan Rombel</div>
                    <div class="dpf-header-sub">Semua siswa aktif di rombel kelas 6 tahun ajaran aktif</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Rombel <span class="text-red">*</span></div></div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="gradClassroom">
                            @forelse ($graduationClassrooms as $c)
                                <option value="{{ $c->classroom_id }}">{{ $c->classroom_name }}</option>
                            @empty
                                <option value="">- tidak ada rombel kelas 6 -</option>
                            @endforelse
                        </select>
                        <div class="dpf-hint">Siswa yang tidak lulus dapat dihapus dari daftar setelahnya (status kembali Aktif).</div>
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Tanggal Lulus <span class="text-red">*</span></div></div>
                    <div class="dpf-row-right"><input class="form-control" type="date" id="gradDate"></div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_doGraduate"><i class="fa fa-graduation-cap"></i> Luluskan</button>
            </div>
        </div>
    </div>
</div>
