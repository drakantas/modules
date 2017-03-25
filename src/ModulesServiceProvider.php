<?php
namespace Draku\Modules;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;
use Draku\Modules\Loader\Loader;
use Draku\Modules\Explorer\Explorer;

class ModulesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerConfig();
        $this->registerModulesLoader();
        $this->registerModulesExplorer();
    }

    public function boot()
    {
        $this->publishConfig();
        $loader = $this->app->make('modules.loader');
        $loader->->mapModuleFiles();
        $loader->registerLoader();
    }

    private function registerModulesLoader()
    {
        $this->app->singleton('modules.loader', function(Container $app) {
            return new Loader(
                $app['config']->get('modules'),
                $app['files'],
                $app['router'],
                $app['view'],
                $app->bootstrapPath()
            );
        });
    }

    private function registerModulesExplorer()
    {
        $this->app->singleton('modules.explorer', function(Container $app) {
            return new Explorer(
                $app['config']->get('modules'),
                $app['files']
            );
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
