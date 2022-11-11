<?php

/**
 * Copyright (c) 2016 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Mock;

use OCA\Files_Antivirus\Scanner\Daemon;
use OCA\Files_Antivirus\Scanner\InitException;
use OCA\Files_Antivirus\Scanner\IScanner;

class ScannerFactory extends \OCA\Files_Antivirus\ScannerFactory {
	/**
	 * @throws InitException
	 */
	public function getScanner(): IScanner {
		return new Daemon($this->appConfig, $this->logger, $this->l10N);
	}
}
