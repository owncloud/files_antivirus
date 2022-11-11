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

class DaemonTest extends TestBase {
	/**
	 * @throws QueryException
	 */
	public function testWrongAntivirusHost(): void {
		$this->expectException(InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->willReturnCallback(function ($methodName) {
				switch ($methodName) {
					case 'getAvHost':
						return 'localhost';
					case 'getAvPort':
						return '9999';
					case 'getAvMode':
						return 'daemon';
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

	/**
	 * @throws QueryException
	 */
	public function testEmptyAntivirusHost(): void {
		$this->expectException(InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->willReturnCallback(function ($methodName) {
				switch ($methodName) {
					case 'getAvHost':
						return '';
					case 'getAvPort':
						return '9999';
					case 'getAvMode':
						return 'daemon';
				}
			})
		;
		$scannerFactory = new ScannerFactory(
			$config,
			$this->container->query('Logger'),
			$this->container->query(IL10N::class)
		);

		$scannerFactory->getScanner();
	}

	/**
	 * @throws QueryException
	 */
	public function testEmptyAntivirusPort(): void {
		$this->expectException(InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->willReturnCallback(function ($methodName) {
				switch ($methodName) {
					case 'getAvHost':
						return 'localhost';
					case 'getAvPort':
						return '';
					case 'getAvMode':
						return 'daemon';
				}
			})
		;
		$scannerFactory = new ScannerFactory(
			$config,
			$this->container->query('Logger'),
			$this->container->query(IL10N::class)
		);

		$scannerFactory->getScanner();
	}
}
