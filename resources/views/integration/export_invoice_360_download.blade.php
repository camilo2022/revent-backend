@extends('layouts.integration')

@section('title', 'Exportación en proceso')

@section('content')
    <x-integration.download-result report="facturación 360" :emails="$emails" />
@endsection
