<?php

$configManager = new Zend\Expressive\ConfigManager\ConfigManager(array(
), 'data/cache/config.php');

return $configManager->getMergedConfig();
