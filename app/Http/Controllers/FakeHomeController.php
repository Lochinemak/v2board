<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FakeHomeController extends Controller
{
    /**
     * 显示伪装的首页
     * 模拟一个普通的企业网站
     */
    public function index(Request $request)
    {
        // 检查是否有特殊参数来访问真实页面
        $accessKey = $request->get('access');
        $validKey = md5(config('app.key') . date('Y-m-d'));

        // 也支持简单的固定key（可选）
        $simpleKey = substr(md5(config('app.key')), 0, 8);

        if ($accessKey === $validKey || $accessKey === $simpleKey) {
            // 返回真实的首页
            return $this->getRealHomePage($request);
        }

        // 返回伪装页面
        return $this->getFakePage();
    }
    
    /**
     * 获取真实的首页
     */
    private function getRealHomePage(Request $request)
    {
        if (config('v2board.app_url') && config('v2board.safe_mode_enable', 0)) {
            if ($request->server('HTTP_HOST') !== parse_url(config('v2board.app_url'))['host']) {
                abort(403);
            }
        }
        
        $renderParams = [
            'title' => config('v2board.app_name', 'CloudPanel'),
            'theme' => config('v2board.frontend_theme', 'default'),
            'version' => config('app.version'),
            'description' => config('v2board.app_description', 'Secure Network Service'),
            'logo' => config('v2board.logo')
        ];

        if (!config("theme.{$renderParams['theme']}")) {
            $themeService = new \App\Services\ThemeService($renderParams['theme']);
            $themeService->init();
        }

        $renderParams['theme_config'] = config('theme.' . config('v2board.frontend_theme', 'default'));
        return view('theme::' . config('v2board.frontend_theme', 'default') . '.dashboard', $renderParams);
    }
    
    /**
     * 获取伪装页面
     */
    private function getFakePage()
    {
        $fakeHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechCorp Solutions - Enterprise IT Services</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        header { background: #2c3e50; color: white; padding: 1rem 0; }
        nav { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 2rem; }
        .nav-links a { color: white; text-decoration: none; }
        .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4rem 0; text-align: center; }
        .hero h1 { font-size: 3rem; margin-bottom: 1rem; }
        .hero p { font-size: 1.2rem; margin-bottom: 2rem; }
        .btn { display: inline-block; background: #e74c3c; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; }
        .services { padding: 4rem 0; }
        .services h2 { text-align: center; margin-bottom: 3rem; }
        .service-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .service-card { background: #f8f9fa; padding: 2rem; border-radius: 8px; text-align: center; }
        footer { background: #2c3e50; color: white; text-align: center; padding: 2rem 0; }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">TechCorp Solutions</div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>
    
    <section class="hero">
        <div class="container">
            <h1>Enterprise IT Solutions</h1>
            <p>Empowering businesses with cutting-edge technology solutions</p>
            <a href="#services" class="btn">Our Services</a>
        </div>
    </section>
    
    <section class="services" id="services">
        <div class="container">
            <h2>Our Services</h2>
            <div class="service-grid">
                <div class="service-card">
                    <h3>Cloud Infrastructure</h3>
                    <p>Scalable cloud solutions for modern businesses</p>
                </div>
                <div class="service-card">
                    <h3>Network Security</h3>
                    <p>Comprehensive security solutions to protect your data</p>
                </div>
                <div class="service-card">
                    <h3>IT Consulting</h3>
                    <p>Expert guidance for your technology strategy</p>
                </div>
            </div>
        </div>
    </section>
    
    <footer>
        <div class="container">
            <p>&copy; 2024 TechCorp Solutions. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>';
        
        return response($fakeHtml)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Server', 'Apache/2.4.41')
            ->header('X-Powered-By', 'PHP/8.1.0');
    }
}
