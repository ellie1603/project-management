@extends('errors.layout')

@section('code', '404')
@section('title', 'Page not found')
@section('message', 'The page you were looking for doesn\'t exist. It may have been moved, or the project or record was deleted.')
@section('actions')
    <a href="{{ url('/dashboard') }}" class="inline-flex rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Go to Dashboard</a>
    <a href="javascript:history.back()" class="inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Go back</a>
@endsection
