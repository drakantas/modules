<?php
namespace Draku\Modules;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;
use Draku\Modules\Loader\Loader;
use Draku\Modules\Loader\Explorer;

class ModulesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerModulesLoader();
    }

    public function boot()
    {
        $this->app->make('modules.loader')->mapModuleFiles();
    }

    private function registerModulesLoader()
    {
        $this->app->singleton('modules.loader', function(Container $app) {
            return new Loader(
                $app,
                $app->make('files'),
                $app->make('router'),
                $app->make('view')
            );
        });
    }
}
