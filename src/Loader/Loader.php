<?php
namespace Draku\Modules\Loader;

use Illuminate\View\Factory as View;
use Illuminate\Routing\Router;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Draku\Modules\Loader\Explorer;
use Draku\Modules\Exceptions\DirectoryHandlerNotFound;

class Loader
{
    /**
     * Application instance
     *
     * @var Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Filesystem instance
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Modules explorer instance
     *
     * @var Draku\Modules\Loader\Explorer
     */
    protected $explorer;

    /**
     * Namespace to locate the modules in this application
     *
     * @var string
     */
    protected $namespace;

    /**
     * Directory structure for each module
     *
     * @var array
     */
    protected $directories = [
        'views' => 'Views',
        'routes' => 'Routes',
        'entities' => 'Entities',
        'controllers' => 'Controllers'
    ];

    /**
     * Regex that catches the file name and ignores the extension
     * - The file must begin with an uppercase letter.
     * - Anything that comes afterward should be a letter from the alphabet or a digit.
     * - Only available file extensions are: .php and .hv
     * Example: MyClass123.php is OK
     *          But, myClass.php is NOT OK
     *
     * @var string
     */
    protected $verifier = '%^([A-Z][a-zA-Z\d]+)\.(?:php|hv)$%';

    /**
     * Router instance
     *
     * @var Illuminate\Routing\Router
     */
    protected $router;

    /**
     * Files map
     *
     * @var array
     */
    protected $fileMap = [];

    /**
     * Enable files paths caching
     * If true it won't browse through each module directory looking for files that could be added
     * to the filemap to be send to the composer autoloader for its load.
     * Set to true only in production environments to increase performance.
     *
     * @var boolean
     */
    protected $cache = false;

    /**
     * Class map file with all of the modules' classes
     *
     * @var string
     */
    protected $cacheFile = 'modules.php';

    /**
     * Create a Loader instance
     *
     * @return void
     */
    public function __construct(Application $app, Filesystem $files, Router $router, View $view)
    {
        $this->app  = $app;
        $this->view = $view;
        $this->files = $files;
        $this->router = $router;

        $this->setNamespace();
        $this->setExplorer($app->modulesPath(), $files);
    }

    /**
     * Add modules' class files to the autoloader class map
     *
     * @return void
     */
    public function mapModuleFiles()
    {
        if($this->cache) {
            $this->fileMap = $this->existsMappedClassesFile();
        }
        else if(!$this->cache || !$this->fileMap) {
            $this->fileMap = $this->explorer->getModulesFiles($this->directories);
        }

        $classMap = [];

        foreach($this->fileMap as $moduleName => $moduleDirectories) {
            foreach($moduleDirectories as $dirName => $files) {
                $this->handleFiles($moduleName, $dirName, $files);
            }
        }
    }

    /**
     * Adds controllers to the autoloader classmap
     *
     * @param string $module
     * @param string $directory
     * @param array $files
     *
     * @return void
     */
    protected function handleControllers($module, $directory, $files)
    {
        foreach($files as $file) {
            $buffer = [
                $module,
                $directory,
                basename($file)
            ];
            $this->app->getAutoloader()->addClassMap([
                $this->formatNamespacedClass($buffer) => $this->formatFilePath($buffer)
            ]);
        }
    }

    /**
     * Entities are also added to the classmap
     *
     * @param string $module
     * @param string $directory
     * @param array $files
     *
     * @return void
     */
    protected function handleEntities($module, $directory, $files)
    {
        // We handle entity classes the same way controllers are handled
        $this->handleControllers($module, $directory, $files);
    }

    /**
     * Includes all of a module's route files and also sets the namespace for controllers to be located
     *
     * @param string $module
     * @param string $directory
     * @param array $files
     *
     * @return void
     */
    protected function handleRoutes($module, $directory, $files)
    {
        $this->router->group([
            'namespace' => $this->formatNamespacedClass([
                $module,
                $this->directories['controllers']
            ])
        ], function(Router $router) use ($module, $directory, $files) {
            foreach($files as $file) {
                include_once $this->formatFilePath([
                    $module,
                    $directory,
                    basename($file)
                ]);
            }
        });
    }

    /**
     * Adds the module views directory to the hints array for the view to be located
     *
     * @param string $module
     * @param string $directory
     * @param array $files
     *
     * @return void
     */
    protected function handleViews($module, $directory, $files)
    {
        $this->view->addNamespace($this->formatNamespacedClass([$module]), $this->formatFilePath([
            $module,
            $directory
        ]));
    }

    /**
     * Passes a directory and its files to another method for the files to be handled accordingly
     *
     * @param string $moduleName
     * @param string $dirName
     * @param array $files
     *
     * @return void
     */
    protected function handleFiles(&$moduleName, &$dirName, &$files)
    {
        if(isset($this->directories[strtolower($dirName)])) {
            if(!method_exists($this, $dirHandler = 'handle'.$dirName)) {
                throw new DirectoryHandlerNotFound("Method {$dirHandler}() couldn't be found.");
            }
            $this->{$dirHandler}($moduleName, $dirName, $files);
        }
    }

    /**
     * Formats a file path from a buffer
     *
     * @param array $buffer
     *
     * @return string
     */
    protected function formatFilePath($buffer)
    {
        array_unshift($buffer, $this->app->modulesPath());
        return $this->formatBuffer($buffer, DIRECTORY_SEPARATOR);
    }

    /**
     * Formats a namespaced class from a buffer
     *
     * @param array $buffer
     *
     * @return string
     */
    protected function formatNamespacedClass($buffer)
    {
        array_unshift($buffer, $this->namespace);
        return $this->formatBuffer($buffer, '\\', true);
    }

    /**
     * Get cached class map
     *
     * @return array|bool
     */
    protected function existsMappedClassesFile()
    {
        $filePath = $this->app->bootstrapPath().DIRECTORY_SEPARATOR.
                    'cache'.DIRECTORY_SEPARATOR.
                    $this->cacheFile;
        return $this->files->exists($filePath) ? include $filePath : $this->createCacheFile($filePath);
    }

    /**
     * Creates the modules map file
     *
     * @param string $path
     *
     * @return array
     */
    private function createCacheFile($path)
    {
        $this->files->put($path, "<?php\nreturn [];", false);
        return include $path;
    }

    /**
     * Joins all elements from a buffer with a separator
     *
     * @param array $buffer
     *
     * @return string
     */
     private function formatBuffer($buffer, string $separator, $verifyLast = false)
     {
         $lastIndex = count($buffer) - 1;

         if($verifyLast) {
             $buffer[$lastIndex] = $this->verifyFile($buffer[$lastIndex]);
         }

         return join($separator, $buffer);
     }

    /**
     * Checks with the verifier regex and catches the file name
     *
     * @param string $file
     *
     * @return string
     */
    private function verifyFile($file)
    {
        return preg_replace($this->verifier, '$1', $file);
    }

    /**
     * Set the explorer instance
     *
     * @param string $path
     * @param Filesystem $fileManager
     *
     * @return Draku\Modules\Loader\Explorer
     */
    private function setExplorer($path, $fileManager)
    {
        if(!isset($this->explorer)) {
            $this->explorer = new Explorer($path, $fileManager);
        }
    }

    /**
     * Sets the namespace for the directory where the modules are located
     *
     * @return void
     */
    private function setNamespace()
    {
        if(!isset($this->namespace)) {
            $this->namespace = 'Modules';
        }
    }
}
