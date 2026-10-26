@extends('layouts.integration')

@section('title', 'Reporte de inventario')

@section('content')
    <x-integration.export-form
        title="Generar reporte de inventario"
        subtitle="Configura los filtros y el reporte se enviará a los correos indicados."
        :action="route('siigo.export_inventory_download')"
        form-id="inventoryReportForm"
        :require-new-emails="true">

        <div class="section-title">Tipo de inventario</div>

        <div class="excel-field-group">
            <label for="inventoryType" class="excel-field-label">
                Selecciona el tipo
            </label>
            <select name="inventory_type" id="inventoryType" class="excel-field-select">
                <option value="" {{ old('inventory_type') === null ? 'selected' : '' }}>Ambos (positivo y negativo)</option>
                <option value="positivo" {{ old('inventory_type') === 'positivo' ? 'selected' : '' }}>Solo inventario positivo</option>
                <option value="negativo" {{ old('inventory_type') === 'negativo' ? 'selected' : '' }}>Solo inventario negativo</option>
            </select>
            <div class="excel-field-hint">
                Si no seleccionas ninguno, el reporte incluirá ambos tipos.
            </div>
        </div>

        @error('inventory_type')
            <div class="excel-error show">{{ $message }}</div>
        @enderror
    </x-integration.export-form>
@endsection
