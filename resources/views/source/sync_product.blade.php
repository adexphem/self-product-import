@extends('templates.layouts')

@section('content')
    <div class="">
        <div class="clearfix"></div>
        <div class="container">
            @if (session('sync_msg'))
                <div class="card-alert card-alert-success cart-alert-750 ">
                    {{ session('sync_msg') }}
                </div>
            @endif
            <div class="connect-card card-max-width-750 centre">
                <div class="source-logo">
                    <img src="/images/{{ $source }}_logo.svg" title="{{ $source }}">
                </div>
                    <a href="{{ $syncLink }}" class="btn btn-mindbody">Start Import</a>
            </div>
        </div>
    </div>
@stop