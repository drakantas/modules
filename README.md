# Draku modules

Modules is a library that allows you to split functionality into modules in a Laravel application without having to write service providers and additional configuration.

## Personal opinion

This library is currently on a very early stage, there's many things that should be optimized and another bunch that need to be worked on, such as a cache. I recommend you to install the `dev-master` version.

## Requirements

- PHP 7.1.2 or higher.
- Laravel 5.4

## Configuration

There is hardly anything to do to set this up:

1. Add `Draku\Modules\ModulesServiceProvider::class` to your autoloaded service providers list.
2. Run `php artisan vendor:publish` just in case you want to customize your installation.
3. That's it, you're ready to go.

## Documentation

Soon.

## File structure

You may not change this file structure because it's currently hard coded.

```
<Your Laravel application>/
    modules/
        Auth/
            Controllers/
                LoginController.php
            Entities/
                User.php
            Routes/
                Auth.php
            Views/
                login.blade.php
                redirect.php
```

## TO DO

- ~~Separate configuration from classes.~~
- Allow UrlGenerator to find modules' controllers.
- Class map cache.
- CLI functionality.
- Unit tests.
