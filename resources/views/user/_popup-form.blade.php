<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-user"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah Pengguna</div>
                    <div class="dpf-header-sub" id="modal-subtitle">Daftarkan pengguna baru</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-sign-in"></i></div>
                        <div>
                            <div class="dpf-label">Jenis Login <span class="text-red">*</span></div>
                            <div class="dpf-sub">Cara pengguna masuk</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <label class="radio-inline"><input type="radio" name="addEditLoginType" value="AD"> Active Directory</label>
                        <label class="radio-inline"><input type="radio" name="addEditLoginType" value="LOCAL" checked> Akun lokal</label>
                        <div class="dpf-hint">Login Active Directory belum tersedia di versi ini &mdash; gunakan akun lokal.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-at"></i></div>
                        <div>
                            <div class="dpf-label">Username <span class="text-red">*</span></div>
                            <div class="dpf-sub">Tidak bisa diubah setelah disimpan</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditUsername" type="text" maxlength="50" oninput="removeWhiteSpace(this)" placeholder="mis. siti.aminah" autocomplete="off" />
                    </div>
                </div>

                <div class="dpf-row" id="rowPassword">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-lock"></i></div>
                        <div>
                            <div class="dpf-label">Password <span class="text-red" id="passwordRequired">*</span></div>
                            <div class="dpf-sub">Minimal 8 karakter</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditPassword" type="password" maxlength="100" autocomplete="new-password" />
                        <div class="dpf-hint" id="passwordHint">Wajib untuk akun lokal.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-id-card-o"></i></div>
                        <div>
                            <div class="dpf-label">Nama Lengkap <span class="text-red">*</span></div>
                            <div class="dpf-sub">NIP / No. Pegawai (opsional)</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditFullName" type="text" maxlength="100" placeholder="Nama lengkap" />
                        <input class="form-control" id="addEditEmployeeNo" type="text" maxlength="20" placeholder="NIP / No. Pegawai" style="margin-top: 8px;" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-envelope-o"></i></div>
                        <div>
                            <div class="dpf-label">Kontak</div>
                            <div class="dpf-sub">Email &amp; no HP (opsional)</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditEmail" type="email" maxlength="100" placeholder="Email" />
                        <input class="form-control" id="addEditPhone" type="text" maxlength="20" placeholder="No HP" style="margin-top: 8px;" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-user-circle-o"></i></div>
                        <div>
                            <div class="dpf-label">Atasan Langsung</div>
                            <div class="dpf-sub">Opsional</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditSupervisor"></select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-key"></i></div>
                        <div>
                            <div class="dpf-label">Peran <span class="text-red">*</span></div>
                            <div class="dpf-sub">Menentukan menu yang bisa diakses</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditRole"></select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-toggle-on"></i></div>
                        <div>
                            <div class="dpf-label">Status</div>
                            <div class="dpf-sub">Pengguna nonaktif tidak bisa login</div>
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

<div class="modal fade sg-modal" id="modalResetPassword" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-key"></i></div>
                <div>
                    <div class="dpf-header-title">Reset Password</div>
                    <div class="dpf-header-sub" id="resetPasswordUser"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body" style="padding: 14px 22px;">
                <input type="hidden" id="resetPasswordId" />
                <div class="form-group">
                    <label for="resetPasswordNew">Password Baru</label>
                    <input type="password" class="form-control" id="resetPasswordNew" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="resetPasswordConfirm">Ulangi Password Baru</label>
                    <input type="password" class="form-control" id="resetPasswordConfirm" autocomplete="new-password">
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_resetPasswordSave"><i class="fa fa-save"></i> Reset</button>
            </div>
        </div>
    </div>
</div>
