@extends('layouts.admin')

@section('title', $journal->kind === \App\Enums\PublicationKind::Newspaper ? __('Gazetani tahrirlash') : __('Jurnalni tahrirlash'))

@section('content')
    @include('pages.admin.journals.partials.form')
@endsection
