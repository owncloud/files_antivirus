<?php

global $RUNTIME_NOAPPS;
$RUNTIME_NOAPPS = true;

if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}

require_once __DIR__.'/../../../lib/base.php';

\OC_Hook::clear();
\OC_App::loadApp('files_antivirus');
