<?php

$aggregator = new \Zend\ConfigAggregator\ConfigAggregator(array(
), 'data/cache/config.php');

return $aggregator->getMergedConfig();
