<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="/static/panel/components.chunk.css?v={{$version}}">
    <link rel="stylesheet" href="/static/panel/umi.css?v={{$version}}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/static/panel/custom.css?v={{$version}}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no">
    <title>{{$title}}</title>
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700"> -->
    <script>window.routerBase = "/";</script>
    <script>
        window.settings = {
            title: '{{$title}}',
            assets_path: '/static/panel',
            theme: {
                sidebar: '{{$theme_sidebar}}',
                header: '{{$theme_header}}',
                color: '{{$theme_color}}',
            },
            version: '{{$version}}',
            background_url: '{{$background_url}}',
            logo: '{{$logo}}',
            secure_path: '{{$secure_path}}',
            oauth_providers: {
                linuxdo: {
                    name: 'Linux.Do',
                    url: '/api/v1/passport/oauth/linuxdo/redirect',
                    icon: 'fab fa-linux',
                    color: '#f39c12'
                }
            },
            oauth_enabled: true
        }
    </script>
</head>

<body>
<div id="root"></div>
<script src="/static/panel/vendors.async.js?v={{$version}}"></script>
<script src="/static/panel/components.async.js?v={{$version}}"></script>
<script src="/static/panel/umi.js?v={{$version}}"></script>
<script src="/static/panel/oauth-admin.js?v={{$version}}"></script>
<script src="/static/panel/user-avatar.js?v={{$version}}"></script>
</body>

</html>
