<div class="box-header dp-toolbar">
    <div class="dp-toolbar-left">
        @if ($add)
            <button type="button" class="dp-btn dp-btn-primary" id="btn_add"><i class="fa fa-plus"></i> Tambah Tahun Ajaran</button>
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
