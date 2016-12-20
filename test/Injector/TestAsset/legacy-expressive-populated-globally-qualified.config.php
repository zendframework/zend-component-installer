<?php

$configManager = new \Zend\Expressive\ConfigManager\ConfigManager(array(
    \Foo\Bar::class,
    \Application\ConfigProvider::class,
), 'data/cache/config.php');

return $configManager->getMergedConfig();
