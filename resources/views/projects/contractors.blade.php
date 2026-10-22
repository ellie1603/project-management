@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-breadcrumbs />

    @include('projects.partials.contractors')
</div>
@endsection
