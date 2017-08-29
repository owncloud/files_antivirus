<?php

/**
 * Copyright (c) 2017 Victor Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OCA\Files_antivirus\Tests;

use OCA\Files_Antivirus\BackgroundScanner;
use OCA\Files_Antivirus\ScannerFactory;
use Doctrine\DBAL\Driver\PDOStatement;

class BackgroundScannerTest extends TestBase {

	public function testGetFilesForScan(){
		
		$scannerFactory = new Mock\ScannerFactory(
			new Mock\Config($this->container->query('CoreConfig')),
			$this->container->query('Logger')
		);
		
		$scannerMock = $this->getMockBuilder('OCA\Files_Antivirus\BackgroundScanner')
			->setConstructorArgs([
				$scannerFactory,
				$this->l10n,
				$this->config,
				\OC::$server->getRootFolder(),
				\OC::$server->getUserSession()
			])
			->getMock();
		
		$class = new \ReflectionClass($scannerMock);
		$method = $class->getMethod('getFilesForScan');
		$method->setAccessible(true);
		$result = $method->invokeArgs($scannerMock, []);
		$this->assertEquals('Doctrine\DBAL\Driver\PDOStatement', get_class($result));
	}

}
