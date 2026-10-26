@extends('layouts.integration')

@section('title', 'Exportación en proceso')

@section('content')
    <x-integration.download-result report="inventario" :emails="$emails" />
@endsection
