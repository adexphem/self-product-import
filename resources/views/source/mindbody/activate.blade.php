@extends('templates.mindbody.pending')

@section('content')
    @include('templates.partials.source-logo')

    <mindbody-activation-popup _jwt="{{ $jwt }}" activationLink="{{ $activationLink }}" studioId="{{ $studioId }}"></mindbody-activation-popup>
@stop
