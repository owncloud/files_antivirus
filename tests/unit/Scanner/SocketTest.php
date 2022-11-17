<?php
/**
 * Copyright (c) 2017 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\Scanner\InitException;
use OCA\Files_Antivirus\ScannerFactory;
use OCA\Files_Antivirus\Tests\unit\TestBase;
use OCP\AppFramework\QueryException;
use OCP\IL10N;

class SocketTest extends TestBase {
	/**
	 * @throws QueryException
	 */
	public function testWrongAntivirusSocket(): void {
		$this->expectException(InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->willReturnCallback(function ($methodName) {
				switch ($methodName) {
					case 'getAvSocket':
						return '/some/wrong/socket.sock';
					case 'getAvMode':
						return 'socket';
				}
			})
		;
		$scannerFactory = new ScannerFactory(
			$config,
			$this->container->query('Logger'),
			$this->container->query(IL10N::class)
		);

		$scanner = $scannerFactory->getScanner();
		$scanner->initScanner('test.txt');
	}
}
