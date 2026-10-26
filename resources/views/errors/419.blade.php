@extends('errors.layout')

@section('code', '419')
@section('title', 'Your session expired')
@section('message', 'The page was open too long, so it was refreshed for your security. Go back, reload the page, and try again. Nothing was saved.')
@section('actions')
    <a href="javascript:history.back()" class="inline-flex rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Go back</a>
    <a href="{{ url('/login') }}" class="inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Sign in again</a>
@endsection
