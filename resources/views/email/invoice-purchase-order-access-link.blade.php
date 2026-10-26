@extends('layouts.email')

@section('title', 'Acceso a Ordenes de compra - REVENT CALZADO S.A.S.')

@section('content')
    <x-email.access-link module="Ordenes de compra" :url="$url" />
@endsection
