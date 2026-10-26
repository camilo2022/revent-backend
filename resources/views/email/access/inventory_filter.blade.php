@extends('layouts.email')

@section('title', 'Acceso a Filtro de inventario - REVENT CALZADO S.A.S.')

@section('content')
    <x-email.access-link module="Filtro de inventario" :url="$url" />
@endsection
