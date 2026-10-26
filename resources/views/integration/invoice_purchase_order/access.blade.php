@extends('layouts.integration')

@section('title', 'Acceso a Ordenes de compra')
@section('nav_minimal', '1')

@section('content')
    <x-integration.access-form
        title="Acceso a Ordenes de compra"
        :action="route('siigo.invoice_purchase_order_send_access_link')"
        hint="Solo correos autorizados del equipo de compras/tecnología pueden solicitar acceso." />
@endsection
