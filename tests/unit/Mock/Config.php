<?php

/**
 * Copyright (c) 2016 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Mock;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\Tests\util\DummyClam;

class Config extends AppConfig {
	public function getAppValue($key) {
		$avirHost = \getenv('AVIR_HOST');
		if ($avirHost === false) {
			$avirHost = '127.0.0.1';
		}
		$map = [
			'av_host' => $avirHost,
			'av_port' => 5555,
			'av_stream_max_length' => DummyClam::TEST_STREAM_SIZE,
			'av_mode' => 'daemon',
			'av_max_file_size' => '-1'
		];
		if (\array_key_exists($key, $map)) {
			return $map[$key];
		}
		return '';
	}
}
