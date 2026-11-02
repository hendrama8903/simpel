<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-calendar"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah Tahun Ajaran</div>
                    <div class="dpf-header-sub" id="modal-subtitle"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar"></i></div>
                        <div>
                            <div class="dpf-label">Tahun Ajaran <span class="text-red">*</span></div>
                            <div class="dpf-sub">Format YYYY/YYYY</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="9" placeholder="mis. 2026/2027" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar-o"></i></div>
                        <div>
                            <div class="dpf-label">Ganjil - Mulai <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditS1Start" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar-o"></i></div>
                        <div>
                            <div class="dpf-label">Ganjil - Selesai <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditS1End" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-print"></i></div>
                        <div>
                            <div class="dpf-label">Ganjil - Tanggal Rapor</div>
                            <div class="dpf-sub">Dicetak di rapor</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditS1Report" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar-o"></i></div>
                        <div>
                            <div class="dpf-label">Genap - Mulai <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditS2Start" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar-o"></i></div>
                        <div>
                            <div class="dpf-label">Genap - Selesai <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditS2End" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-print"></i></div>
                        <div>
                            <div class="dpf-label">Genap - Tanggal Rapor</div>
                            <div class="dpf-sub">Dicetak di rapor</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditS2Report" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-check-circle"></i></div>
                        <div>
                            <div class="dpf-label">Semester Aktif</div>
                            <div class="dpf-sub">Dipakai default di semua menu</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditActive">
                            <option value="0">Tidak aktif</option>
                            <option value="1">Ganjil</option>
                            <option value="2">Genap</option>
                        </select>
                        <div class="dpf-hint">Mengaktifkan semester di sini menonaktifkan semester aktif sebelumnya. Pengguna perlu memuat ulang halaman.</div>
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
