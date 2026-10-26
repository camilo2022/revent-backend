@extends('layouts.integration')

@section('title', 'Exportación en proceso')

@section('content')
    <x-integration.download-result report="compra" :emails="$emails" />
@endsection
