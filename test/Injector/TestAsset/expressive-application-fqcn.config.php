<?php

$aggregator = new Zend\ConfigAggregator\ConfigAggregator(array(
    Application\ConfigProvider::class,
), 'data/cache/config.php');

return $aggregator->getMergedConfig();
