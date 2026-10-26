@extends('layouts.integration')

@section('title', 'Reporte de compra')

@section('content')
    <x-integration.export-form
        title="Generar reporte de compra"
        subtitle="Elige un mes completo o un rango de fechas manual. El reporte se enviará a los correos indicados."
        :action="route('siigo.export_purchase_download')"
        form-id="purchaseReportForm"
        :period="true" />
@endsection
