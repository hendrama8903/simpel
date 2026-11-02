<div class="box-header dp-toolbar">
    <div class="dp-toolbar-left">
        @if ($add)
            <button type="button" class="dp-btn dp-btn-primary" id="btn_add"><i class="fa fa-plus"></i> Tambah Mutasi</button>
        @endif
        @if ($edit)
            <button type="button" class="dp-btn dp-btn-outline" id="btn_edit" disabled><i class="fa fa-pencil-square-o"></i> Ubah</button>
        @endif
        @if ($delete)
            <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_delete" disabled><i class="fa fa-trash-o"></i> Hapus</button>
        @endif
        @if ($add)
            <button type="button" class="dp-btn dp-btn-outline" id="btn_graduate"><i class="fa fa-graduation-cap"></i> Luluskan Rombel</button>
        @endif
        <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Muat Ulang</button>
    </div>
    <div class="dp-toolbar-right">
        <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Ekspor Excel</button>
    </div>
</div>
<div class="sg-filterbar">
    <div class="sg-field">
        <label for="filterType">Jenis</label>
        <select class="form-control" id="filterType">
            <option value="">Semua</option>
            @foreach (\App\Repositories\StudentExitRepo::Types as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="sg-field">
        <label for="filterYear">Tahun</label>
        <input class="form-control" type="number" id="filterYear" min="2000" max="2100" value="{{ date('Y') }}" style="width: 110px; min-width: 0;">
    </div>
</div>
