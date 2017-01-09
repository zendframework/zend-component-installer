<?php

$configManager = new \Zend\Expressive\ConfigManager\ConfigManager(array(
    \Application\ConfigProvider::class,
), 'data/cache/config.php');

return $configManager->getMergedConfig();
