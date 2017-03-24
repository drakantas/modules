<?php
namespace Archivum\Modules\Loader;

use Illuminate\Filesystem\Filesystem;
use Draku\Modules\Exceptions\ManifestNotFound;

class Explorer
{
    protected $files;
    protected $collection;
    protected $modulesPath;
    protected $manifestFile = 'manifest.json';

    public function __construct($modulesPath, Filesystem $files)
    {
        $this->files = $files;
        $this->modulesPath = $modulesPath;
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
