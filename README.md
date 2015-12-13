# Component Installer for Zend Framework 3 Applications

This repository contains the class `Zend\ComponentInstaller\ComponentInstaller`,
which provides Composer event hooks for the events:

- post-package-install
- post-package-uninstall

In order to utilize these, you will need to add the `ComponentInstaller`
classfile to your project, make it autoloadable, and then add its relevant
static methods as scripts for the above events.

This package provides two ways for doing that: as a global composer utility, or
via a downloadable, self-updateable PHAR.

## Via Composer Global Install

To install the utility via Composer:

```bash
$ composer global require zendframework/component-installer
```

Once installed, assuming that the Composer `bin/` directory is on your `$PATH`,
you can then execute the following:

```bash
$ component-installer install <path>
```

where `<path>` is the path to a project in which you want to install the
component installer tools. If `<path>` is omitted, the utility assumes the
current working directory should be used.

> ### Note: Composer installation not yet supported!
>
> The above is a *planned* feature, but does not work currently, as the package
> is not yet registered with Packagist.

## Via PHAR

The PHAR file is downloadable at:

- https://weierophinney.github.io/component-installer/component-installer.phar

The public key for verifying the package is at:

- https://weierophinney.github.io/component-installer/component-installer.phar.pubkey

You will need to download both files, to the same directory, for the utility to
work; additionally, the name of the key must not be changed.. Once downloaded,
make the the PHAR file executable.

Once installed, you can then execute the following:

```bash
$ component-installer.phar install <path>
```

where `<path>` is the path to a project in which you want to install the
component installer tools. If `<path>` is omitted, the utility assumes the
current working directory should be used.

The PHAR file is self-updateable via the `self-update` command; this feature
requires PHP 5.6, however, due to SSL/TLS negotiation requirements.
