# Draku modules

Modules is a library that allows you to split functionality into modules in a Laravel application without having to write service providers and additional configuration.

## Requirements

- PHP 7.1.2 or higher.
- Laravel 5.4

## Configuration

Right now, you need to do certain tweaks in order for this library to work, if you can figure out what to do then go ahead and do it but if not then you'll have to wait or use a different library.

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

- Separate configuration from classes.
- Allow UrlGenerator to find modules' controllers.
- Class map cache.
- CLI functionality.
- Unit tests.
