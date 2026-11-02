<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-user"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah Guru</div>
                    <div class="dpf-header-sub" id="modal-subtitle"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-user"></i></div>
                        <div>
                            <div class="dpf-label">Nama Lengkap <span class="text-red">*</span></div>
                            <div class="dpf-sub">Beserta gelar</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="100" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-id-card-o"></i></div>
                        <div>
                            <div class="dpf-label">NIP</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditNip" type="text" maxlength="30" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-id-card-o"></i></div>
                        <div>
                            <div class="dpf-label">NUPTK</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditNuptk" type="text" maxlength="30" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-venus-mars"></i></div>
                        <div>
                            <div class="dpf-label">Jenis Kelamin <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditGender">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-map-marker"></i></div>
                        <div>
                            <div class="dpf-label">Tempat Lahir</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditBirthPlace" type="text" maxlength="50" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar"></i></div>
                        <div>
                            <div class="dpf-label">Tanggal Lahir</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditBirthDate" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-briefcase"></i></div>
                        <div>
                            <div class="dpf-label">Jabatan <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditPosition">
                            <option value="GURU_KELAS">Guru Kelas</option>
                            <option value="GURU_MAPEL">Guru Mapel</option>
                            <option value="KEPSEK">Kepala Sekolah</option>
                            <option value="TENDIK">Tenaga Kependidikan</option>
                        </select>
                        <div class="dpf-hint">Menentukan role saat akun login dibuat.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-certificate"></i></div>
                        <div>
                            <div class="dpf-label">Status Kepegawaian</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditEmployment">
                            <option value="">-</option>
                            <option value="PNS">PNS</option>
                            <option value="PPPK">PPPK</option>
                            <option value="GTY">GTY</option>
                            <option value="GTT">GTT</option>
                            <option value="HONORER">Honorer</option>
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-phone"></i></div>
                        <div>
                            <div class="dpf-label">No HP</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditPhone" type="text" maxlength="20" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-envelope-o"></i></div>
                        <div>
                            <div class="dpf-label">Email</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditEmail" type="text" maxlength="100" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-home"></i></div>
                        <div>
                            <div class="dpf-label">Alamat</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <textarea class="form-control" id="addEditAddress" rows="2" maxlength="255"></textarea>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-key"></i></div>
                        <div>
                            <div class="dpf-label">Akun Login</div>
                            <div class="dpf-sub">Hubungkan ke user yang sudah ada</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditUser"></select>
                        <div class="dpf-hint">Kosongkan lalu pakai tombol Buat Akun Login untuk membuat akun baru.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-toggle-on"></i></div>
                        <div>
                            <div class="dpf-label">Status</div>
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
