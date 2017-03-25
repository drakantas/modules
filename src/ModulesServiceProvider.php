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
        $this->registerConfig();
        $this->registerModulesLoader();
    }

    public function boot()
    {
        $this->publishConfig();
        $this->app->make('modules.loader')->mapModuleFiles();
    }

    private function registerModulesLoader()
    {
        $this->app->singleton('modules.loader', function(Container $app) {
            $loader = new Loader(
                $app,
                $app->make('files'),
                $app->make('router'),
                $app->make('view')
            );
            $loader->setNamespace(config('modules.namespace'));
            return $loader;
        });
    }

    private function publishConfig()
    {
        $this->publishes([
            __DIR__.'/../config/modules.php' => config_path('modules.php')
        ]);
    }

    private function registerConfig()
    {
         $this->mergeConfigFrom(
             __DIR__.'/../config/modules.php',
             'modules'
         );
    }
}
