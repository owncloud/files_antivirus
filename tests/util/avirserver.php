<?php

namespace OCA\Files_Antivirus\Tests\util;

include __DIR__ . '/DummyClam.php';

\set_time_limit(0);
$socketPath = 'tcp://0.0.0.0:5555';
echo 'starting DummyClam on ' . $socketPath . PHP_EOL;
$clam = new DummyClam($socketPath);
$clam->startServer();
