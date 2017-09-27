<!DOCTYPE html>
<html lang="en">
<head>
    @include('templates.partials.source-meta')
</head>

<body class="nav-md">
<div class="container body">
    <div class="main_container">

        <!-- page content -->
        <div class="right_col" role="main" id="app">
            {{--@include('templates.partials.alerts')--}}
            @yield('content')
        </div>
        <!-- /page content -->

    </div>
</div>

<!-- Custom Scripts can come in here -->
<script type="text/javascript" src="{{ mix('js/app.js') }}"></script>

</body>
</html>