<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-calendar-check-o"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah Ujian</div>
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
                            <div class="dpf-label">Semester <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditSemester">
                            @foreach ($semesters as $s)
                                <option value="{{ $s->SemesterId }}">{{ $s->Label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-tag"></i></div>
                        <div>
                            <div class="dpf-label">Kode Ujian <span class="text-red">*</span></div>
                            <div class="dpf-sub">Awalan nomor peserta</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditCode" type="text" maxlength="15" oninput="removeWhiteSpace(this)" style="text-transform: uppercase;" placeholder="mis. SAS2627" />
                        <div class="dpf-hint">Nomor peserta: KODE-KELAS-URUT, mis. SAS2627-1-001. Tidak bisa diubah.</div>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-file-text-o"></i></div>
                        <div>
                            <div class="dpf-label">Nama Ujian <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="100" placeholder="mis. Sumatif Akhir Semester Ganjil" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-list"></i></div>
                        <div>
                            <div class="dpf-label">Jenis <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditType">
                            <option value="STS">Sumatif Tengah Semester</option>
                            <option value="SAS">Sumatif Akhir Semester</option>
                            <option value="ASAJ">Asesmen Sumatif Akhir Jenjang</option>
                            <option value="LAINNYA">Lainnya</option>
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar-o"></i></div>
                        <div>
                            <div class="dpf-label">Tanggal Mulai <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditStart" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-calendar-o"></i></div>
                        <div>
                            <div class="dpf-label">Tanggal Selesai <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditEnd" type="date" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-sticky-note-o"></i></div>
                        <div>
                            <div class="dpf-label">Catatan Kartu</div>
                            <div class="dpf-sub">Dicetak di kartu ujian</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <textarea class="form-control" id="addEditNote" rows="2" maxlength="255"></textarea>
                        <div class="dpf-hint">mis. Kartu wajib dibawa setiap hari ujian.</div>
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
