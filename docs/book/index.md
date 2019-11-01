```bash
$ composer require --dev zendframework/zend-component-installer
```

> ### Global Installation
>
> You can also install the plugin globally, in which case it will be active for
> every project you manage on your machine.
>
> ```bash
> $ composer global require zendframework/zend-component-installer
> ```

## Installable Packages

How do you define a package for which zend-component-installer will install
configuration?

### Components

Components are Zend Framework modules that deliver low-level
functionality; examples include the various Zend Framework components
themselves. These require the following:

* A `Module` class in the package namespace.
* An `extra.zf.component` entry listing the package namespace in your
  `composer.json`.

```json
"extra": {
    "zf": {
        "component": "Some\\Component"
    }
}
```

You may also specify multiple components as an array:

```json
"extra": {
    "zf": {
        "component": [
            "Some\\Component",
            "Other\\Component"
        ]
    }
}
```

Your application will need to have one or more of the following configuration
files, from which you will be prompted to choose which one in which to inject
the component:

* `config/application.config.php` (vanilla ZF2 application)
* `config/modules.config.php` ([Apigility](https://apigility.org) application)
* `config/development.config.php.dist` and `config/development.config.php`
  (applications using [zf-development-mode](https://github.com/zfcampus/zf-development-mode))

Components are added at the **top** of the application's list of modules.

### Modules

Zend Framework modules typically deliver functionality around the
[zend-mvc](https://docs.zendframework.com/zend-mvc/) workflow, including MVC
event listeners, controllers, etc. To enable the installer workflow, they 
require the following:

* A `Module` class in the package namespace.
* An `extra.zf.module` entry listing the package namespace in your `composer.json`.

```json
"extra": {
    "zf": {
        "module": "Some\\Component"
    }
}
```

You may also specify multiple modules as an array:

```json
"extra": {
    "zf": {
        "module": [
            "Some\\Component",
            "Other\\Component"
        ]
    }
}
```

Your application will need to have one or more of the following configuration
files, from which you will be prompted to choose which one in which to inject
the module:

* `config/application.config.php` (vanilla ZF2 application)
* `config/modules.config.php` ([Apigility](https://apigility.org) application)
* `config/development.config.php.dist` and `config/development.config.php`
  (applications using [zf-development-mode](https://github.com/zfcampus/zf-development-mode))

Modules are added at the **bottom** of the application's list of modules.

### Config Providers

Configuration providers work with [expressive-config-manager](https://github.com/mtymek/expressive-config-manager)
and [zend-config-aggregator](https://github.com/zendframework/zend-config-aggregator),
which provides generic functionality for aggregating and merging application
configuration. Packages that provide configuration will provide an invokable
class that returns configuration for the package. To enable the installer
workflow, you will need:

* A configuration provider class. This is a class with no constructor
  arguments defining an `__invoke()` method returning a configuration array.

```php
namespace Some\Component;

class ConfigProvider
{
    public function __invoke()
    {
        return [ /* ... */ ];
    }
}
```

* An `extra.zf.config-provider` entry listing the configuration provider class
  in your `composer.json`.

```json
"extra": {
    "zf": {
        "config-provider": "Some\\Component\\ConfigProvider"
    }
}
```

You may also specify multiple configuration providers as an array:

```json
"extra": {
    "zf": {
        "config-provider": [
            "Some\\Component\\ConfigProvider",
            "Some\\Component\\PluginConfigProvider"
        ]
    }
}
```

Your application will need to define a `config/config.php` file, and that file
will need to have a line that instantiates either a
`Zend\Expressive\ConfigManager\ConfigManager` instance (deprecated) or
`Zend\ConfigAggregator\ConfigAggregator` instance.

Configuration providers are added at the **top** of the
`ConfigManager`/`ConfigAggregator` provider array.

## Whitelisting packages to autoinstall

At the root package level, you can indicate that certain packages that supply
config providers and/or modules should automatically inject configuration,
instead of prompting for installation, via the `component-whitelist` setting.
This value should be an array of package names.

```json
"extra": {
    "zf": {
        "component-whitelist": [
            "zendframework/zend-expressive",
            "zendframework/zend-expressive-helper",
            "zendframework/zend-expressive-fastrouterouter",
            "zendframework/zend-expressive-platesrenderer"
        ]
    }
}
```

This setting only works in the root package.

## Why?

When preparing zend-mvc's version 3 release, we wanted to reduce the number of
components required by the package. To do so, we moved integration code, such as
factories, plugin managers, and event listeners into the components they
consumed. This had a side effect: the components were no longer wired
automatically.

To provide service and event wiring, we added `Module` classes (and
configuration providers) to all Zend Framework components. This exposed a new
problem, however: how could we ensure that those components are added to the
application module list as you add them to your application?

This package provides the answer to that problem. As soon as you add this
package to your application, whenever you add a component or module that exposes
itself as such, the plugin will prompt you, asking where you want to inject it.

## When are multiple items required?

As noted under each of the component, module, and config-provider sections, you
can optionally specify an _array_ of items. When would you do this?

The primary reason is for [metapackages](https://getcomposer.org/doc/04-schema.md#type).
Composer does not trigger either `post-package-install` or
`post-package-uninstall` events for packages defined as metapackage
requirements. As such, you would define the metadata for such packages in the
metapackage as well, to ensure that the component installer can update the
configuration accordingly.

The other use case is to allow specifying multiple configuration providers from
the same package. As an example, you might define default configuration, plus
configuration for plugins; specifying these as separate configuration providers
allows the consumer to choose if they want both enabled in their application.

### Removing individual packages

When removing individual packages that were originally installed via a
metapackage, _the component installer will not trigger_. As such, you will need
to manually remove such packages from your configuration.

Additionally, a later update may actually _re-install_ the package, as it's a
requirement of the metapackage. As such, it's typically safer to:

* Remove the metapackage
* Individually install the packages from the metapackage that you wish to keep.
