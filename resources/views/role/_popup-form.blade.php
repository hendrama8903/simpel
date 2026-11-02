<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-users"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah Peran</div>
                    <div class="dpf-header-sub" id="modal-subtitle">Buat peran baru</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-tag"></i></div>
                        <div>
                            <div class="dpf-label">Kode Peran <span class="text-red">*</span></div>
                            <div class="dpf-sub">Kode unik peran</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditCode" type="text" maxlength="30" oninput="removeWhiteSpace(this)" placeholder="mis. BENDAHARA" style="text-transform: uppercase;" />
                        <div class="dpf-hint">Tidak bisa diubah setelah disimpan.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-file-o"></i></div>
                        <div>
                            <div class="dpf-label">Nama Peran <span class="text-red">*</span></div>
                            <div class="dpf-sub">Nama tampilan</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="100" placeholder="Masukkan nama peran" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-align-left"></i></div>
                        <div>
                            <div class="dpf-label">Keterangan</div>
                            <div class="dpf-sub">Opsional</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <textarea class="form-control" id="addEditDescription" rows="2" maxlength="250"></textarea>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-toggle-on"></i></div>
                        <div>
                            <div class="dpf-label">Status</div>
                            <div class="dpf-sub">Pengguna dengan peran nonaktif tidak bisa login</div>
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
