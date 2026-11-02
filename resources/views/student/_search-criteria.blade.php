<div class="box-header dp-toolbar">
    <div class="dp-toolbar-left">
        @if ($add)
            <button type="button" class="dp-btn dp-btn-primary" id="btn_add"><i class="fa fa-plus"></i> Tambah Siswa</button>
        @endif
        @if ($edit)
            <button type="button" class="dp-btn dp-btn-outline" id="btn_edit" disabled><i class="fa fa-pencil-square-o"></i> Ubah</button>
        @endif
        @if ($delete)
            <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_delete" disabled><i class="fa fa-trash-o"></i> Hapus</button>
        @endif
        @if ($upload)
            <button type="button" class="dp-btn dp-btn-outline" id="btn_upload"><i class="fa fa-upload"></i> Unggah Excel</button>
        @endif
        <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
    </div>
    <div class="dp-toolbar-right">
        <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Ekspor Excel</button>
    </div>
</div>
<div class="sg-filterbar">
    <div class="sg-field">
        <label for="filterStatus">Status</label>
        <select class="form-control" id="filterStatus">
            <option value="">Semua</option>
            @foreach (\App\Repositories\StudentRepo::Statuses as $code => $label)
                <option value="{{ $code }}" @selected($code === 'AKTIF')>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="sg-field">
        <label for="filterClassroom">Rombel {{ $yearName }}</label>
        <select class="form-control" id="filterClassroom">
            <option value="">Semua</option>
            @foreach ($classrooms as $c)
                <option value="{{ $c->classroom_id }}">{{ $c->classroom_name }}</option>
            @endforeach
        </select>
    </div>
</div>
