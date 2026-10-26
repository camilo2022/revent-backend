@extends('layouts.email')

@section('title', 'Acceso a Cuentas por pagar - REVENT CALZADO S.A.S.')

@section('content')
    <x-email.access-link module="Cuentas por pagar" :url="$url" />
@endsection
