@extends('layouts.app')
@section('page-title','Tambah Supplier')
@section('content')
<div class="card border-0 shadow-sm"><div class="card-body">
<form method="POST" action="{{ route('suppliers.store') }}">@csrf
@include('suppliers._form')
<div class="d-flex gap-2"><button class="btn btn-primary">Simpan</button><a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Kembali</a></div>
</form></div></div>
@endsection
