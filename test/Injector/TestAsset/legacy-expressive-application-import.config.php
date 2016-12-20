<?php
use Zend\Expressive\ConfigManager\ConfigManager;

$configManager = new ConfigManager(array(
    Application\ConfigProvider::class,
), 'data/cache/config.php');

return $configManager->getMergedConfig();
