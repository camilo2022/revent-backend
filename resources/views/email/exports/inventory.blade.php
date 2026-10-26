@extends('layouts.email')

@section('body_style', 'margin:0; padding:0; background:#f4f4f4; font-family: Arial, Helvetica, sans-serif;')

@section('content')
    <x-email.export-result label="inventario" :success="$success" :filename="$filename ?? null" :download-url="$downloadUrl ?? null" :error-message="$errorMessage ?? null" />
@endsection
