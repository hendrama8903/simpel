<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-bullseye"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah TP</div>
                    <div class="dpf-header-sub" id="modal-subtitle"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>

            <div class="dpf-body">
                <input id="addEditId" type="hidden" />

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-book"></i></div>
                        <div>
                            <div class="dpf-label">Mata Pelajaran <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditSubject">
                            @foreach ($subjects as $s)
                                <option value="{{ $s->subject_id }}">{{ $s->subject_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-signal"></i></div>
                        <div>
                            <div class="dpf-label">Kelas <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditGrade">
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
                        <div class="dpf-icon"><i class="fa fa-calendar"></i></div>
                        <div>
                            <div class="dpf-label">Semester <span class="text-red">*</span></div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditSemesterNo">
                            <option value="1">Ganjil</option>
                            <option value="2">Genap</option>
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-tag"></i></div>
                        <div>
                            <div class="dpf-label">Kode</div>
                            <div class="dpf-sub">Kosongkan = otomatis TP berikutnya</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditCode" type="text" maxlength="10" oninput="removeWhiteSpace(this)" style="text-transform: uppercase;" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-align-left"></i></div>
                        <div>
                            <div class="dpf-label">Deskripsi <span class="text-red">*</span></div>
                            <div class="dpf-sub">Tulis sebagai frasa</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <textarea class="form-control" id="addEditDescription" rows="4" maxlength="500"></textarea>
                        <div class="dpf-hint">Contoh: "membaca teks pendek dengan lafal dan intonasi yang tepat". Rapor: "Ananda ... menunjukkan penguasaan dalam <deskripsi>".</div>
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
