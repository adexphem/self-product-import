@extends('templates.layouts')

@section('content')
    <div>
        <div class="clearfix"></div>
        <div class="container">
            <div class="connect-card card-max-width-750 centre">
                <div class="error error-message">
                    We're unable to authenticate this user at the moment.
                </div>
                <hmac-error-redir-page></hmac-error-redir-page>
            </div>
        </div>
    </div>
@stop