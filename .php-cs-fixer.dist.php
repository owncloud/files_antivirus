<?php

$config = new OC\CodingStandard\Config();

$config
    ->setUsingCache(true)
    ->getFinder()
    ->exclude(['vendor','build'])
    ->in(__DIR__);

return $config;