{{-- Ekstrakurikuler: add/edit/delete, export Excel. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('extracurricular._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('extracurricular._popup-form')
@endsection

@section('scripts')
@include('extracurricular._scripts')
@endsection
