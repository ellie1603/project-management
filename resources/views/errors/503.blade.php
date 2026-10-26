@extends('errors.layout')

@section('code', '503')
@section('title', 'Down for maintenance')
@section('message', 'The system is being updated and will be back shortly. Your work is saved.')
@section('actions')
    <a href="javascript:location.reload()" class="inline-flex rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Try again</a>
@endsection
