<!doctype html>
<html class="no-js" lang="">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Larakommerce</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Place favicon.ico in the root directory -->
    <link rel="icon" href="/favicon.ico">

    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>

<header class="lk-topbar">
    <div class="top-bar">
        <div class="top-bar-title">
        <span data-responsive-toggle="responsive-menu" data-hide-for="medium">
          <button class="menu-icon dark" type="button" data-toggle></button>
        </span>
            <strong>Larakommerce</strong>
        </div>
        <div id="responsive-menu">
            <div class="top-bar-left">
                <ul class="menu">
                    <li><a href="#">Two</a></li>
                    <li><a href="#">Three</a></li>
                </ul>
            </div>
            <div class="top-bar-right">
                <ul class="menu">
                    {{--<li><input type="search" placeholder="Search"></li>--}}
                    {{--<li><button type="button" class="button">Search</button></li>--}}
                </ul>
            </div>
        </div>
    </div>

</header>

<div id="lk-app"></div>

<script src="{{ asset('assets/common.js') }}"></script>
<script src="{{ asset('assets/app.js') }}"></script>
<script src="{{ asset('assets/package.js') }}"></script>

</body>
</html>