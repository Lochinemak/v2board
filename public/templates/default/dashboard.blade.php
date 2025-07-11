<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="/templates/{{$theme}}/assets/components.chunk.css?v={{$version}}">
    <link rel="stylesheet" href="/templates/{{$theme}}/assets/umi.css?v={{$version}}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @if (file_exists(public_path("/templates/{$theme}/assets/custom.css")))
        <link rel="stylesheet" href="/templates/{{$theme}}/assets/custom.css?v={{$version}}">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no">
    @php ($colors = [
        'darkblue' => '#3b5998',
        'black' => '#343a40',
        'default' => '#0665d0',
        'green' => '#319795'
    ])
    <meta name="theme-color" content="{{$colors[$theme_config['theme_color']]}}">

    <title>{{$title}}</title>
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700"> -->
    <script>window.routerBase = "/";</script>
    <script>
        window.settings = {
            title: '{{$title}}',
            assets_path: '/templates/{{$theme}}/assets',
            theme: {
                sidebar: '{{$theme_config['theme_sidebar']}}',
                header: '{{$theme_config['theme_header']}}',
                color: '{{$theme_config['theme_color']}}',
            },
            version: '{{$version}}',
            background_url: '{{$theme_config['background_url']}}',
            description: '{{$description}}',
            i18n: [
                'zh-CN',
                'en-US',
                'ja-JP',
                'vi-VN',
                'ko-KR',
                'zh-TW',
                'fa-IR'
            ],
            logo: '{{$logo}}',
            oauth_providers: {
                linuxdo: {
                    name: 'Linux.Do',
                    url: '/api/v1/passport/oauth/linuxdo/redirect',
                    icon: 'fab fa-linux',
                    color: '#f39c12'
                }
            }
        }
    </script>
    <script src="/templates/{{$theme}}/assets/i18n/zh-CN.js?v={{$version}}"></script>
    <script src="/templates/{{$theme}}/assets/i18n/zh-TW.js?v={{$version}}"></script>
    <script src="/templates/{{$theme}}/assets/i18n/en-US.js?v={{$version}}"></script>
    <script src="/templates/{{$theme}}/assets/i18n/ja-JP.js?v={{$version}}"></script>
    <script src="/templates/{{$theme}}/assets/i18n/vi-VN.js?v={{$version}}"></script>
    <script src="/templates/{{$theme}}/assets/i18n/ko-KR.js?v={{$version}}"></script>
    <script src="/templates/{{$theme}}/assets/i18n/fa-IR.js?v={{$version}}"></script>
</head>

<body>
<div id="root"></div>
{!! $theme_config['custom_html'] !!}
<script src="/templates/{{$theme}}/assets/vendors.async.js?v={{$version}}"></script>
<script src="/templates/{{$theme}}/assets/components.async.js?v={{$version}}"></script>
<script src="/templates/{{$theme}}/assets/umi.js?v={{$version}}"></script>
@if (file_exists(public_path("/templates/{$theme}/assets/custom.js")))
    <script src="/templates/{{$theme}}/assets/custom.js?v={{$version}}"></script>
@endif
</body>

</html>
