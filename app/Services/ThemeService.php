<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ThemeService
{
    private $path;
    private $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
        $this->path = $path = public_path('templates/');
    }

    public function init()
    {
        $themeConfigFile = $this->path . "{$this->theme}/config.json";
        if (!File::exists($themeConfigFile)) abort(500, "{$this->theme}模板不存在");
        $themeConfig = json_decode(File::get($themeConfigFile), true);
        if (!isset($themeConfig['configs']) || !is_array($themeConfig)) abort(500, "{$this->theme}模板配置文件有误");
        $configs = $themeConfig['configs'];
        $data = [];
        foreach ($configs as $config) {
            $data[$config['field_name']] = isset($config['default_value']) ? $config['default_value'] : '';
        }

        $data = var_export($data, 1);
        try {
            if (!File::put(base_path() . "/config/templates/{$this->theme}.php", "<?php\n return $data ;")) {
                abort(500, "{$this->theme}模板初始化失败");
            }
        } catch (\Exception $e) {
            abort(500, '请检查系统目录权限');
        }

        try {
            Artisan::call('config:cache');
            while (true) {
                if (config("templates.{$this->theme}")) break;
            }
        } catch (\Exception $e) {
            abort(500, "{$this->theme}模板初始化失败");
        }
    }
}
