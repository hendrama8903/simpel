{{-- Tahun ajaran + semester Ganjil/Genap. Tepat satu semester aktif di seluruh sistem. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('academic-year._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('academic-year._popup-form')
@endsection

@section('scripts')
@include('academic-year._scripts')
@endsection
