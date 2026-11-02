<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-book"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah Mapel</div>
                    <div class="dpf-header-sub" id="modal-subtitle"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-tag"></i></div>
                        <div>
                            <div class="dpf-label">Kode <span class="text-red">*</span></div>
                            <div class="dpf-sub">Kode singkat, mis. MTK</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditCode" type="text" maxlength="10" oninput="removeWhiteSpace(this)" style="text-transform: uppercase;" />
                        <div class="dpf-hint">Tidak bisa diubah setelah disimpan.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-book"></i></div>
                        <div>
                            <div class="dpf-label">Nama Mapel <span class="text-red">*</span></div>
                            <div class="dpf-sub">Ditampilkan di rapor</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="100" placeholder="mis. Matematika" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-th-large"></i></div>
                        <div>
                            <div class="dpf-label">Kelompok</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditGroup">
                            @foreach (\App\Repositories\SubjectRepo::Groups as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-user"></i></div>
                        <div>
                            <div class="dpf-label">Diajar Oleh</div>
                            <div class="dpf-sub">Guru kelas = dinilai wali kelas</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditTaughtBy">
                            @foreach (\App\Repositories\SubjectRepo::TaughtBy as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="dpf-hint">Guru mapel (mis. PAI, PJOK) diatur di menu Tugas Mengajar.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-sort-numeric-asc"></i></div>
                        <div>
                            <div class="dpf-label">Urutan Rapor</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditOrder" type="number" min="0" max="999" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-toggle-on"></i></div>
                        <div>
                            <div class="dpf-label">Status</div>
                            <div class="dpf-sub">Mapel nonaktif tidak muncul di penilaian &amp; rapor</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <label class="sg-switch"><input type="checkbox" id="addEditIsActive" checked /> Aktif</label>
                    </div>
                </div>
            </div>

            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_save"><i class="fa fa-save"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>
