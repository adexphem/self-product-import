@extends('templates.mindbody.pending')

@section('content')
    <div>
        @include('templates.partials.source-logo')
        <h4><b>Let's get started.</b></h4>
        <p>Connect your Weebly site to MINDBODY by providing your MINDBODY Site ID.</p>
        <form action="/mindbody/activationcode" method="POST">
            {{ csrf_field() }}
            <div class="form-control form-input-flex">
                <input type="number" placeholder="MINDBODY Site ID" required pattern="/0-9/" name="studio_id" value="{{ $studioId or null }}">
                <input type="hidden" name="jwt" value="{{ $jwt }}">
            </div>

            <div class="form-control control-buttons">
                <button class="btn-primary btn-primary-mindbody" type="submit">Continue</button>
            </div>
        </form>
    </div>
@stop
