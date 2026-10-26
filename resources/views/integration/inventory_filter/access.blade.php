@extends('layouts.integration')

@section('title', 'Acceso a Filtro de inventario')
@section('nav_minimal', '1')

@section('content')
    <x-integration.access-form
        title="Acceso a Filtro de inventario"
        :action="route('siigo.inventory_filter_send_access_link')"
        hint="Solo correos registrados en siigo pueden solicitar acceso." />
@endsection
