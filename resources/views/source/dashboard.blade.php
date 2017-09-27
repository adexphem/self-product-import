@extends('templates.layouts')

@section('content')
    <div class="">
        <div class="clearfix"></div>
        <div class="container">
            <div class="connect-card card-max-width-750 centre">
                <div class="block-title">Welcome to <br> Product Import with</div>
                <div class="source-logo">
                    <img src="/images/{{ $source }}_logo.svg" title="{{ $source }}">
                </div>
                <a href="{{ route('source.connect', 'shapeways') . '?jwt='. $jwt }}" class="btn btn-mindbody">Connect</a>
            </div>
        </div>
    </div>
@stop