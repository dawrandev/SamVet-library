@extends('layouts.admin')

@section('title', $article->journalIssue?->journal?->kind === \App\Enums\PublicationKind::Newspaper ? __('Gazeta maqolasini tahrirlash') : __('Maqolani tahrirlash'))

@section('content')
    @include('pages.admin.articles.partials.form')
@endsection
