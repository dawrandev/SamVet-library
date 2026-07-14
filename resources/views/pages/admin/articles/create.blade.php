@extends('layouts.admin')

@section('title', ($kind ?? null) === \App\Enums\PublicationKind::Newspaper->value ? __('Yangi gazeta maqolasi') : __('Yangi maqola'))

@section('content')
    @include('pages.admin.articles.partials.form')
@endsection
