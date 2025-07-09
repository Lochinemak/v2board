<?php

use App\Services\ThemeService;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'FakeHomeController@index');

// 兼容旧的assets路径，重定向到新路径
Route::get('/assets/admin/{path}', function($path) {
    return redirect('/static/panel/' . $path, 301);
})->where('path', '.*');

// 兼容旧的theme路径
Route::get('/theme/{path}', function($path) {
    return redirect('/templates/' . $path, 301);
})->where('path', '.*');

//TODO:: 兼容
Route::get('/' . \App\Utils\AdminPathGenerator::getCurrentPath(), function () {
    return view('admin', [
        'title' => config('v2board.app_name', 'CloudPanel'),
        'theme_sidebar' => config('v2board.frontend_theme_sidebar', 'light'),
        'theme_header' => config('v2board.frontend_theme_header', 'dark'),
        'theme_color' => config('v2board.frontend_theme_color', 'default'),
        'background_url' => config('v2board.frontend_background_url'),
        'version' => config('app.version'),
        'logo' => config('v2board.logo'),
        'secure_path' => \App\Utils\AdminPathGenerator::getCurrentPath()
    ]);
});

if (!empty(config('v2board.subscribe_path'))) {
    Route::get(config('v2board.subscribe_path'), 'V1\\Client\\ClientController@subscribe')->middleware('client');
}