@extends('layouts.admin')

@section('content')
<div class="box-header dp-toolbar">
    <div class="dp-toolbar-left">
        @if ($add)
            <button type="button" class="dp-btn dp-btn-primary" id="btn_add" title="Tambah di bawah baris terpilih"><i class="fa fa-plus"></i> <span id="btnAddLabel">Tambah Menu</span></button>
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
        <button type="button" class="dp-btn dp-btn-outline" id="btn_expand"><i class="fa fa-plus-square-o"></i> Buka Semua</button>
        <button type="button" class="dp-btn dp-btn-outline" id="btn_collapse"><i class="fa fa-minus-square-o"></i> Tutup Semua</button>
        <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Ekspor Excel</button>
    </div>
</div>
<section class="content">
    <div class="dp-card">
        <div id="divGrid">
            @include('menu._grid')
        </div>
        <div class="mn-note">
            <i class="fa fa-info-circle"></i>
            <b>Menu</b> = grup di sidebar, <b>Halaman</b> = layar dengan URL, <b>Tombol</b> = izin aksi di dalam halaman (mis. STUDENT-ADD).
            Menu/tombol baru otomatis diberikan ke peran Administrator; berikan ke peran lain di Peran &amp; Akses.
            Perubahan terlihat setelah pengguna login ulang.
        </div>
    </div>
</section>
@include('menu._popup-form')
@endsection

@section('scripts')
@include('menu._scripts')
@endsection
