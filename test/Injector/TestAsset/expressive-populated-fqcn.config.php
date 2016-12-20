<?php

$aggregator = new Zend\ConfigAggregator\ConfigAggregator(array(
    \Foo\Bar::class,
    Application\ConfigProvider::class,
), 'data/cache/config.php');

return $aggregator->getMergedConfig();
