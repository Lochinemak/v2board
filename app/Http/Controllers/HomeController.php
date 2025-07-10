<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ThemeService;

class HomeController extends Controller
{
    /**
     * 显示首页
     */
    public function index(Request $request)
    {
        if (config('v2board.app_url') && config('v2board.safe_mode_enable', 0)) {
            if ($request->server('HTTP_HOST') !== parse_url(config('v2board.app_url'))['host']) {
                abort(403);
            }
        }

        $renderParams = [
            'title' => config('v2board.app_name', 'V2Board'),
            'theme' => config('v2board.frontend_theme', 'default'),
            'version' => config('app.version'),
            'description' => config('v2board.app_description', 'V2Board is best'),
            'logo' => config('v2board.logo')
        ];

        if (!config("templates.{$renderParams['theme']}")) {
            $themeService = new ThemeService($renderParams['theme']);
            $themeService->init();
        }

        $renderParams['theme_config'] = config('templates.' . config('v2board.frontend_theme', 'default'));
        return view('theme::' . config('v2board.frontend_theme', 'default') . '.dashboard', $renderParams);
    }
}
