<?php
use Zend\Expressive\ConfigManager\ConfigManager;

$configManager = new ConfigManager(array(
    \Foo\Bar::class,
    Application\ConfigProvider::class,
), 'data/cache/config.php');

return $configManager->getMergedConfig();
