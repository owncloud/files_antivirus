<?php
/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Cron;

use OCA\Files_Antivirus\Cron\Task;
use OCA\Files_Antivirus\ScannerFactory;
use OCA\Files_Antivirus\Tests\unit\Mock\Config as ConfigMock;
use OCA\Files_Antivirus\Tests\unit\Mock\ScannerFactory as ScannerMock;
use OCA\Files_Antivirus\Tests\unit\TestBase;
use Doctrine\DBAL\Driver\Statement;
use OCP\Files\IRootFolder;
use OCP\IUser;

class TaskTest extends TestBase {
	/** @var  ScannerFactory */
	protected $scannerFactory;

	public function setUp() {
		parent::setUp();
		//Background scanner requires at least one user on the current instance
		$userManager = $this->application->getContainer()->query('ServerContainer')->getUserManager();
		$results = $userManager->search('', 1, 0);

		if (!\count($results)) {
			\OC::$server->getUserManager()->createUser('test', 'test');
		}
		$this->scannerFactory = new ScannerFactory(
			$this->config,
			$this->container->query('Logger')
		);
	}
	
	public function testRun() {
		$cronMock = new Task(
			$this->container->getServer()->getUserSession(),
			$this->container->getServer()->getLogger(),
			$this->container->getServer()->getRootFolder(),
			$this->l10n,
			$this->scannerFactory,
			$this->container->query('AppConfig'),
			$this->container->query('FileCollection')
		);

		$class = new \ReflectionClass($cronMock);
		$method = $class->getMethod('run');
		$method->setAccessible(true);
		$result = $method->invokeArgs($cronMock, ['']);
		$this->assertNull($result);
	}

	public function testGetFilesForScan() {
		$scannerFactory = new ScannerMock(
			new ConfigMock($this->container->query('CoreConfig')),
			$this->container->query('Logger')
		);

		$cronMock = $this->getMockBuilder(Task::class)
			->setConstructorArgs([
				\OC::$server->getUserSession(),
				$this->container->getServer()->getLogger(),
				\OC::$server->getRootFolder(),
				$this->l10n,
				$scannerFactory,
				$this->config,
				$this->container->query('FileCollection')
			])
			->getMock();

		$class = new \ReflectionClass($cronMock);
		$method = $class->getMethod('getFilesForScan');
		$method->setAccessible(true);
		$result = $method->invokeArgs($cronMock, []);
		$this->assertInstanceOf(Statement::class, $result);
	}
}
