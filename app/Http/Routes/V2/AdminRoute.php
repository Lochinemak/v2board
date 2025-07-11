<?php
namespace App\Http\Routes\V2;

use Illuminate\Contracts\Routing\Registrar;

class AdminRoute
{
    public function map(Registrar $router)
    {
        $router->group([
            'prefix' => \App\Utils\AdminPathGenerator::getCurrentPath(),
            'middleware' => ['admin', 'log'],
        ], function ($router) {
            // Stat
            $router->get ('/stat/override', 'V2\\Admin\\StatController@override');
            $router->get ('/stat/record', 'V2\\Admin\\StatController@record');
            $router->get ('/stat/ranking', 'V2\\Admin\\StatController@ranking');
        });
    }
}
