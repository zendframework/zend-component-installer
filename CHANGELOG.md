# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.3.0 - 2016-06-27

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#4](https://github.com/zendframework/zend-component-installer/pull/4) removes
  support for PHP 5.5.

### Fixed

- [#8](https://github.com/zendframework/zend-component-installer/pull/8) fixes
  how the `DevelopmentConfig` discovery and injection works. Formerly, these
  were looking for the `development.config.php` file; however, this was
  incorrect. zf-development-mode has `development.config.php.dist` checked into
  the repository, but specifically excludes `development.config.php` from it in
  order to allow toggling it from the `.dist` file. The code now correctly does
  this.

## 0.2.0 - 2016-06-02

### Added

- [#5](https://github.com/zendframework/zend-component-installer/pull/5) adds
  support for arrays of components/modules/config-providers, in the format:

  ```json
  {
    "extra": {
      "zf": {
        "component": [
          "Some\\Component",
          "Other\\Component"
        ]
      }
    }
  }
  ```

  This feature should primarily be used for metapackages, or config-providers
  where some configuration might not be required, and which could then be split
  into multiple providers.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.0 - TBD

First tagged release.

Previously, PHAR releases were created from each push to the master branch.
Starting in 0.1.0, the architecture changes to implement a
[composer plugin](https://getcomposer.org/doc/articles/plugins.md). As such,
tagged releases now make more sense, as plugins are installed via composer
(either per-project or globally).

### Added

- [#2](https://github.com/zendframework/zend-component-installer/pull/2) adds:
  - All classes in the `Zend\ComponentInstaller\ConfigDiscovery` namespace.
    These are used to determine which configuration files are present and
    injectable in the project.
  - All classes in the `Zend\ComponentInstaller\Injector` namespace. These are
    used to perform the work of injecting and removing values from configuration
    files.
  - `Zend\ComponentInstaller\ConfigOption`, a value object mapping prompt text
    to its related injector.
  - `Zend\ComponentInstaller\ConfigDiscovery`, a class that loops over known
    configuration discovery types to return a list of `ConfigOption` instances

### Deprecated

- Nothing.

### Removed

- [#2](https://github.com/zendframework/zend-component-installer/pull/2) removes
  all classes in the `Zend\ComponentInstaller\Command` namespace.
- [#2](https://github.com/zendframework/zend-component-installer/pull/2) removes
  the various `bin/` scripts.
- [#2](https://github.com/zendframework/zend-component-installer/pull/2) removes
  the PHAR distribution.

### Fixed

- [#2](https://github.com/zendframework/zend-component-installer/pull/2) updates
  `Zend\ComponentInstaller\ComponentInstaller`:
  - to act as a Composer plugin.
  - to add awareness of additional configuration locations:
    - `modules.config.php` (Apigility)
    - `development.config.php` (zend-development-mode)
    - `config.php` (Expressive with expressive-config-manager)
  - to discover and prompt for known configuration locations when installing a
    package.
  - to allow re-using a configuration selection for remaining packages in the
    current install session.
