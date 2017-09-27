<!DOCTYPE html>
<html lang="en">
<head>
    @include('templates.partials.source-meta')
</head>

<body class="nav-md bg-mindbody_connected">
<div class="container body">
    <div class="card" id="app">
        @yield('content')
    </div>
</div>

<!-- Custom Scripts can come in here -->
<script type="text/javascript" src="{{ mix('js/app.js') }}"></script>

</body>
</html>