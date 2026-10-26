@extends('errors.layout')

@section('code', '500')
@section('title', 'Something went wrong')
@section('message', 'The system ran into a problem and couldn\'t finish. The error has been logged. Please try again in a moment, and tell the System Administrator if it keeps happening.')
@section('actions')
    <a href="{{ url('/dashboard') }}" class="inline-flex rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Go to Dashboard</a>
    <a href="javascript:location.reload()" class="inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Try again</a>
@endsection
