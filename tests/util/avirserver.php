<?php

namespace OCA\Files_Antivirus\Tests\util;

include __DIR__ . '/DummyClam.php';

\set_time_limit(0);
$avirHost = \getenv('AVIR_HOST');
if ($avirHost === false) {
	$avirHost = '0.0.0.0';
}
$socketPath = "tcp://$avirHost:5555";
echo 'starting DummyClam on ' . $socketPath . PHP_EOL;
$clam = new DummyClam($socketPath);
$clam->startServer();
