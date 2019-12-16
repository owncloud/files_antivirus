<?php
/**
 * Copyright (c) 2017 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\ScannerFactory;
use OCA\Files_Antivirus\Tests\unit\TestBase;

class SocketTest extends TestBase {
	/**
	 */
	public function testWrongAntivirusSocket() {
		$this->expectException(\OCA\Files_Antivirus\Scanner\InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->will($this->returnCallback(
				function ($methodName) {
					switch ($methodName) {
						case 'getAvSocket':
							return  '/some/wrong/socket.sock';
						case 'getAvMode':
							return 'socket';
					}
				}
			))
		;
		$scannerFactory = new ScannerFactory(
			$config,
			$this->container->query('Logger')
		);

		$scanner = $scannerFactory->getScanner();
		$scanner->initScanner();
	}
}
