@extends('layouts.integration')

@section('title', 'Reporte de facturación')

@section('content')
    <x-integration.export-form
        title="Generar reporte de facturación"
        subtitle="Elige un mes completo o un rango de fechas manual. El reporte se enviará a los correos indicados."
        :action="route('siigo.export_invoice_download')"
        form-id="invoiceReportForm"
        :period="true" />
@endsection
