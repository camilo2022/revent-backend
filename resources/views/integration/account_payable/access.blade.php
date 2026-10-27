@extends('layouts.integration')

@section('title', 'Acceso a Cuentas por pagar')

@section('content')
    <x-integration.access-form
        title="Acceso a Cuentas por pagar"
        :action="route('siigo.account_payable_send_access_link')"
        hint="Solo correos autorizados del equipo de contabilidad/tecnología pueden solicitar acceso." />
@endsection
