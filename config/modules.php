<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    |
    | This path defines where modules will be stored.
    |
    */
    'path' => base_path('modules'),
    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Set to true if you want to use the mapped classes found in the cache
    | file, you'll need to manually update the mentioned file whenever you
    | make a change any module file or directory.
    |
    */
    'cache' => false,
    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for the modules directory where all modules are stored.
    |
    */
    'namespace' => 'Modules',
    /*
    |--------------------------------------------------------------------------
    | Cache file
    |--------------------------------------------------------------------------
    |
    | This value defines the cache file where mapped class files will be
    | found.
    |
    */
    'cacheFile' => 'modules.php',
    /*
    |--------------------------------------------------------------------------
    | Manifest file
    |--------------------------------------------------------------------------
    |
    | This value defines the manifest file that the explorer will find to
    | obtain a module's data.
    |
    */
    'manifestFile' => 'manifest.json',
    /*
    |--------------------------------------------------------------------------
    | Directory structure
    |--------------------------------------------------------------------------
    |
    | This array defines how a module's directories are structured. You should
    | only alter the values and leave the keys as they are, otherwise you'll
    | run into errors.
    |
    */
    'dirStructure' => [
        'views' => 'Views',
        'routes' => 'Routes',
        'entities' => 'Entities',
        'controllers' => 'Controllers'
    ]
];
