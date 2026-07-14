@extends('layouts.admin')

@section('title', ($kind ?? null) === \App\Enums\PublicationKind::Newspaper->value ? __('Yangi gazeta') : __('Yangi jurnal'))

@section('content')
    @include('pages.admin.journals.partials.form')
@endsection
