@props(['name', 'class' => 'h-6 w-6'])

{{-- Thin component wrapper around the shared icon partial, for use as a tag at top
     level. Inside another anonymous component, @include the partial directly instead. --}}
@include('partials.admin.icon', ['name' => $name, 'class' => $class])
