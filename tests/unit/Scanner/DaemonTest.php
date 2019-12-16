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

class DaemonTest extends TestBase {
	/**
	 */
	public function testWrongAntivirusHost() {
		$this->expectException(\OCA\Files_Antivirus\Scanner\InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->will($this->returnCallback(
				function ($methodName) {
					switch ($methodName) {
						case 'getAvHost':
							return  'localhost';
						case 'getAvPort':
							return  '9999';
						case 'getAvMode':
							return 'daemon';
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

	/**
	 */
	public function testEmptyAntivirusHost() {
		$this->expectException(\OCA\Files_Antivirus\Scanner\InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->will($this->returnCallback(
				function ($methodName) {
					switch ($methodName) {
						case 'getAvHost':
							return  '';
						case 'getAvPort':
							return  '9999';
						case 'getAvMode':
							return 'daemon';
					}
				}
			))
		;
		$scannerFactory = new ScannerFactory(
			$config,
			$this->container->query('Logger')
		);

		$scannerFactory->getScanner();
	}

	/**
	 */
	public function testEmptyAntivirusPort() {
		$this->expectException(\OCA\Files_Antivirus\Scanner\InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->will($this->returnCallback(
				function ($methodName) {
					switch ($methodName) {
						case 'getAvHost':
							return  'localhost';
						case 'getAvPort':
							return  '';
						case 'getAvMode':
							return 'daemon';
					}
				}
			))
		;
		$scannerFactory = new ScannerFactory(
			$config,
			$this->container->query('Logger')
		);

		$scannerFactory->getScanner();
	}
}
