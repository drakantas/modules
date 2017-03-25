<?php
namespace Draku\Modules\Loader;

use Illuminate\View\Factory as View;
use Illuminate\Routing\Router;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Draku\Modules\Explorer\Explorer;
use Draku\Modules\Exceptions\DirectoryHandlerNotFound;

class Loader
{
    /**
     * Modules path
     *
     * @var string
     */
    protected $path;

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
    protected $dirStructure = [];

    /**
     * Enable class map caching
     *
     * @var boolean
     */
    protected $cache;

    /**
     * Cache file with mapped classes
     *
     * @var string
     */
    protected $cacheFile;

    /**
     * Manifest file
     *
     * @var string
     */
    protected $manifestFile;

    /**
     * Regex that catches the file name and ignores the extension
     * - The file must begin with an uppercase letter.
     * - Anything that comes afterward should be a letter or a digit.
     * - Only available file extensions are: .php and .hv
     * Example: MyClass123.php is OK
     *          But, myClass.php is NOT OK
     *
     * @var string
     */
    protected $verifier = '%^([A-Z][a-zA-Z\d]+)\.(?:php|hh)$%';

    /**
     * Cache path
     *
     * @var string
     */
    protected $cachePath;

    /**
     * View factory instance
     *
     * @var \Illuminate\View\Factory
     */
    protected $view;
    /**
     * Filesystem instance
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Router instance
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * Modules explorer instance
     *
     * @var \Draku\Modules\Loader\Explorer
     */
    protected $explorer;

    /**
     * Files map
     *
     * @var array
     */
    protected $filesMap = [];

    /**
     * Classes map
     */
    protected $classMap = [];

    /**
     * Create a Loader instance
     *
     * @param array $config
     * @param \Illuminate\View\Factory $view
     * @param \Illuminate\Routing\Router $router
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Draku\Modules\Loader\Explorer $explorer
     *
     * @return void
     */
    public function __construct(
        array $config,
        View $view,
        Router $router,
        Filesystem $files,
        Explorer $explorer,
        $cachePath = '',
    ) {
        $this->view = $view;
        $this->files = $files;
        $this->router = $router;
        $this->explorer = $explorer;
        $this->cachePath = $cachePath;

        $this->setConfig($config);
    }

    /**
     * Add modules' class files to the autoloader class map
     *
     * @return void
     */
    public function mapModuleFiles()
    {
        if($this->cache) {
            $this->filesMap = $this->existsMappedClassesFile();
        }
        else if(!$this->cache || !$this->filesMap) {
            $this->filesMap = $this->explorer->getModulesFiles($this->dirStructure);
        }

        foreach($this->filesMap as $moduleName => $moduleDirectories) {
            foreach($moduleDirectories as $dirName => $files) {
                $this->handleFiles($moduleName, $dirName, $files);
            }
        }
    }

    /**
     * Register the class loader
     *
     * @return void
     */
    public function registerLoader()
    {
        spl_autoload_register([$this, 'loadClass'], true, true);
    }

    /**
     * Load the provided class
     *
     * @param string $class
     *
     * @return bool
     */
    public function loadClass($class)
    {
        if(isset($this->classMap[$class])) {
            includeFile($this->classMap[$class]);
            return true;
        }
        return false;
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
        $classes = [];

        foreach($files as $file) {
            $buffer = [
                $module,
                $directory,
                basename($file)
            ];
            array_push($classes, [$this->formatNamespacedClass($buffer) => $this->formatFilePath($buffer)]);
        }
        array_push($this->classMap, $classes);
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
     * Includes all of a module's route files and also sets the namespace for
     * controllers to be located
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
                $this->dirStructure['controllers']
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
     * Adds the module views directory to the hints array for the view to be
     * located
     *
     * @param string $module
     * @param string $directory
     * @param array $files
     *
     * @return void
     */
    protected function handleViews($module, $directory, $files)
    {
        $this->view->addNamespace(
            $this->formatNamespacedClass([$module]), $this->formatFilePath([
                $module,
                $directory
            ])
        );
    }

    /**
     * Passes a directory and its files to another method for the files to be
     * handled accordingly
     *
     * @param string $moduleName
     * @param string $dirName
     * @param array $files
     *
     * @return void
     *
     * @throws \Draku\Modules\Exceptions\DirectoryHandlerNotFound
     */
    protected function handleFiles(&$moduleName, &$dirName, &$files)
    {
        if(isset($this->dirStructure[strtolower($dirName)])) {
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
        array_unshift($buffer, $this->path);
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
        $filePath = '';
        if($this->cachePath !== '') {
            $filePath = $this->cachePath;
        }
        else {
            $filePath = base_path('cache');
        }
        $filePath .= DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$this->cacheFile;
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
     * Sets the configuration variables
     *
     * @param array $config
     *
     * @return void
     */
    private function setConfig(&$config)
    {
        foreach($config as $k => $v) {
            $this->{$k} = $v;
        }
    }
}

/**
 * Scope isolated include.
 *
 * @author Composer
 */
function includeFile($path)
{
    include $path;
}
