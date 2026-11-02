<div class="box-header dp-toolbar">
    <div class="dp-toolbar-left">
        @if ($add)
            <button type="button" class="dp-btn dp-btn-primary" id="btn_add"><i class="fa fa-plus"></i> Tambah TP</button>
        @endif
        @if ($edit)
            <button type="button" class="dp-btn dp-btn-outline" id="btn_edit" disabled><i class="fa fa-pencil-square-o"></i> Ubah</button>
        @endif
        @if ($delete)
            <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_delete" disabled><i class="fa fa-trash-o"></i> Hapus</button>
        @endif
        <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
    </div>
    <div class="dp-toolbar-right">
        <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Ekspor Excel</button>
    </div>
</div>
<div class="sg-filterbar">
    <div class="sg-field">
        <label for="filterSubject">Mata Pelajaran</label>
        <select class="form-control" id="filterSubject">
            <option value="">Semua</option>
            @foreach ($subjects as $s)
                <option value="{{ $s->subject_id }}">{{ $s->subject_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="sg-field">
        <label for="filterGrade">Kelas</label>
        <select class="form-control" id="filterGrade">
            <option value="">Semua</option>
            @for ($g = 1; $g <= 6; $g++)
                <option value="{{ $g }}">Kelas {{ $g }}</option>
            @endfor
        </select>
    </div>
    <div class="sg-field">
        <label for="filterSemesterNo">Semester</label>
        <select class="form-control" id="filterSemesterNo">
            <option value="">Semua</option>
            <option value="1">Ganjil</option>
            <option value="2">Genap</option>
        </select>
    </div>
</div>
