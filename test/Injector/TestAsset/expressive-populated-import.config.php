<?php
use Zend\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator(array(
    \Foo\Bar::class,
    Application\ConfigProvider::class,
), 'data/cache/config.php');

return $aggregator->getMergedConfig();
