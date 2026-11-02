<div class="modal fade sg-modal" id="modalAddEdit" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-users"></i></div>
                <div>
                    <div class="dpf-header-title" id="modal-title">Tambah Rombel</div>
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
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditYear">
                            @foreach ($years as $y)
                                <option value="{{ $y->academic_year_id }}">{{ $y->year_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-signal"></i></div>
                        <div>
                            <div class="dpf-label">Tingkat <span class="text-red">*</span></div>
                            <div class="dpf-sub">Fase A: 1-2, B: 3-4, C: 5-6</div>
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
                        <div class="dpf-icon"><i class="fa fa-tag"></i></div>
                        <div>
                            <div class="dpf-label">Nama Rombel <span class="text-red">*</span></div>
                            <div class="dpf-sub">mis. 1A, 1B</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="addEditName" type="text" maxlength="20" oninput="removeWhiteSpace(this)" style="text-transform: uppercase;" />
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-user"></i></div>
                        <div>
                            <div class="dpf-label">Wali Kelas</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditHomeroom"></select>
                    </div>
                </div>

                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-icon"><i class="fa fa-building-o"></i></div>
                        <div>
                            <div class="dpf-label">Ruangan</div>
                        </div>
                    </div>
                    <div class="dpf-row-right">
                        <select class="form-control" id="addEditRoom">
                            <option value="">-</option>
                            @foreach ($rooms as $r)
                                <option value="{{ $r->room_id }}">{{ $r->room_name }}</option>
                            @endforeach
                        </select>
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
