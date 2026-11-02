<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-child"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah Siswa</div>
                    <div class="dpf-header-sub" id="modal-subtitle"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row" id="photoBox" style="display:none">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-camera"></i></div>
                        <div>
                            <div class="dpf-label">Foto</div>
                            <div class="dpf-sub">Dipakai di kartu ujian</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <img id="photoImg" alt="Foto siswa" style="width: 90px; height: 120px; object-fit: cover; border: 1px solid #EAECF0; border-radius: 8px; display: none;">
                        <div id="photoEmpty" class="dpf-hint">Belum ada foto.</div>
                        @if ($edit)
                            <input type="file" id="photoFile" accept=".jpg,.jpeg,.png" style="display:none">
                            <div><button type="button" class="dp-btn dp-btn-outline" id="btn_photo" style="margin-top: 6px;"><i class="fa fa-camera"></i> Ganti Foto</button></div>
                            <div class="dpf-hint">JPG/PNG maks. 2 MB, rasio 3x4. Foto langsung tersimpan.</div>
                        @endif
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-id-badge"></i></div>
                        <div>
                            <div class="dpf-label">NIS <span class="text-red">*</span></div>
                            <div class="dpf-sub">Nomor induk sekolah</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditNis" type="text" maxlength="20" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-id-card-o"></i></div>
                        <div>
                            <div class="dpf-label">NISN</div>
                            <div class="dpf-sub">10 digit</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditNisn" type="text" maxlength="10" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-id-card-o"></i></div>
                        <div>
                            <div class="dpf-label">NIK</div>
                            <div class="dpf-sub">16 digit</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditNik" type="text" maxlength="16" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-child"></i></div>
                        <div>
                            <div class="dpf-label">Nama Lengkap <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="100" />
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
                        <div class="dpf-icon"><i class="fa fa-star-o"></i></div>
                        <div>
                            <div class="dpf-label">Agama</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditReligion">
                            <option value="">-</option>
                            <option value="Islam">Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katolik">Katolik</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Konghucu">Konghucu</option>
                        </select>
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
                        <div class="dpf-icon"><i class="fa fa-male"></i></div>
                        <div>
                            <div class="dpf-label">Nama Ayah</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditFather" type="text" maxlength="100" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-female"></i></div>
                        <div>
                            <div class="dpf-label">Nama Ibu</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditMother" type="text" maxlength="100" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-user"></i></div>
                        <div>
                            <div class="dpf-label">Nama Wali</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditGuardian" type="text" maxlength="100" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-phone"></i></div>
                        <div>
                            <div class="dpf-label">No HP Orang Tua</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditParentPhone" type="text" maxlength="20" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-sign-in"></i></div>
                        <div>
                            <div class="dpf-label">Jenis Masuk</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditEntryType">
                            <option value="BARU">Siswa Baru</option>
                            <option value="PINDAHAN">Pindahan</option>
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar-check-o"></i></div>
                        <div>
                            <div class="dpf-label">Tanggal Masuk</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditEntryDate" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-signal"></i></div>
                        <div>
                            <div class="dpf-label">Diterima di Kelas</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditEntryGrade">
                            <option value="1">Kelas 1</option>
                            <option value="2">Kelas 2</option>
                            <option value="3">Kelas 3</option>
                            <option value="4">Kelas 4</option>
                            <option value="5">Kelas 5</option>
                            <option value="6">Kelas 6</option>
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-university"></i></div>
                        <div>
                            <div class="dpf-label">Sekolah Asal</div>
                            <div class="dpf-sub">Untuk siswa pindahan</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditPrevSchool" type="text" maxlength="150" />
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
