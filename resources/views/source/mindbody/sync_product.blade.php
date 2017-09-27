@extends('templates.mindbody.connected')

@section('content')
    @include('templates.partials.source-logo')
    <div class="bordered-top"></div>

    <mindbody-product-sync _synclink="{{ $syncLink }}" _synceddate="{{ $lastSyncedDate }}"
                           _syncedcount="{{ $syncedProducts }}"></mindbody-product-sync>
@stop
