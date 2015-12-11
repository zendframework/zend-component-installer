# Component Installer for Zend Framework 3 Applications

This repository contains the class `Zend\ComponentInstaller\ComponentInstaller`,
which provides Composer event hooks for the events:

- post-package-install
- post-package-uninstall

In order to utilize these, you will need to add the `ComponentInstaller`
classfile to your project, make it autoloadable, and then add its relevant
static methods as scripts for the above events.

To do that, this repository also provides a PHAR file that provides an
installer. It is available at:

- https://weierophinney.github.io/zend-component-installer/component-installer.phar

The public key for verifying the package is at:

- https://weierophinney.github.io/zend-component-installer/component-installer.phar.pubkey

The PHAR file is self-updateable.
