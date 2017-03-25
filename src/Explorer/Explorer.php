<?php
namespace Draku\Modules\Explorer;

use Illuminate\Filesystem\Filesystem;
use Draku\Modules\Exceptions\ManifestNotFound;

class Explorer
{
    /**
     * Modules path
     *
     * @var string
     */
    protected $modulesPath;

    /**
     * Manifest file
     *
     * @var string
     */
    protected $manifestFile;

    /**
     * Filesystem instance
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create an Explorer instance
     *
     * @param string $modulesPath
     * @param string $manifestFile
     * @param \Illuminate\Filesystem\Filesystem $files
     *
     * @return void
     */
    public function __construct(array $config, Filesystem $files)
    {
        $this->files = $files;
        $this->modulesPath = $config['path'];
        $this->manifestFile = $config['manifestFile'];
    }

    /**
     * Paths for all of the module directories
     *
     * @return array
     */
    public function getModules()
    {
        if(!$this->files->exists($this->modulesPath))
        {
            $this->files->makeDirectory($this->modulesPath);
            return;
        }
        return $this->files->directories($this->modulesPath);
    }

    /**
     * Paths for each module that is enabled
     *
     * @return array
     */
    public function getEnabledModules()
    {
        $modules = $this->getModules();
        $enabledModules = [];

        foreach ($modules as $module) {
            if($this->getManifest($module)->enabled) {
                array_push($enabledModules, $module);
            }
        }
        return $enabledModules;
    }

    /**
     * Obtain manifest from a module path
     *
     * @param string $path
     *
     * @return object
     */
    public function getManifest($path)
    {
        $path = $path.DIRECTORY_SEPARATOR.$this->manifestFile;
        $module = strtolower(($buffer = preg_split('%(\\\+|/)%', $path))[count($buffer) - 2]);

        if(!$this->files->exists($path))
            throw new ManifestNotFound("The file {$this->manifestFile} couldn't be found within the module {$module} directory.");

        return json_decode($this->files->get($path));
    }

    /**
     * Files found in all modules that are enabled
     *
     * @param array $directories
     *
     * @return array
     */
    public function getModulesFiles(array $directories)
    {
        $moduleFiles = [];
        $modules = $this->getEnabledModules();

        foreach($modules as $module) {
            foreach($directories as $directory) {
                if($files = $this->files->files($module.DIRECTORY_SEPARATOR.$directory)) {
                    $moduleFiles[basename($module)][$directory] = $files;
                }
            }
        }
        return $moduleFiles;
    }
}
