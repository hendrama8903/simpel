<div class="modal fade sg-modal" id="modalCopy" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-clone"></i></div>
                <div>
                    <div class="dpf-header-title">Salin Rombel</div>
                    <div class="dpf-header-sub">Nama, tingkat, wali kelas &amp; ruangan disalin tanpa siswa</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Dari Tahun Ajaran</div></div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="copySource">
                            @foreach ($years as $y)
                                <option value="{{ $y->academic_year_id }}">{{ $y->year_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="dpf-row">
                    <div class="dpf-row-left"><div class="dpf-label">Ke Tahun Ajaran</div></div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="copyTarget">
                            @foreach ($years as $y)
                                <option value="{{ $y->academic_year_id }}">{{ $y->year_name }}</option>
                            @endforeach
                        </select>
                        <div class="dpf-hint">Rombel yang namanya sudah ada di tahun tujuan dilewati. Siswa dimasukkan lewat menu Anggota Rombel.</div>
                    </div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_doCopy"><i class="fa fa-clone"></i> Salin</button>
            </div>
        </div>
    </div>
</div>
