<?php
/**
 * Copyright (c) 2021 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Cron;

use OCA\Files_Antivirus\Cron\Task;
use OCA\Files_Antivirus\Scanner\InitException;
use OCA\Files_Antivirus\ScannerFactory;
use OCA\Files_Antivirus\Tests\unit\Mock\Config as ConfigMock;
use OCA\Files_Antivirus\Tests\unit\Mock\ScannerFactory as ScannerMock;
use OCA\Files_Antivirus\Tests\unit\TestBase;
use Doctrine\DBAL\Driver\Statement;
use OCP\AppFramework\QueryException;
use OCP\IL10N;
use ReflectionException;

class TaskTest extends TestBase {
	protected ScannerFactory $scannerFactory;

	public function setUp(): void {
		parent::setUp();
		//Background scanner requires at least one user on the current instance
		$userManager = $this->application->getContainer()->query('ServerContainer')->getUserManager();
		$results = $userManager->search('', 1, 0);

		if (!\count($results)) {
			\OC::$server->getUserManager()->createUser('test', 'test');
		}
		$this->scannerFactory = new ScannerFactory(
			$this->config,
			$this->container->query('Logger'),
			$this->container->query(IL10N::class)
		);
	}

	/**
	 * @throws ReflectionException
	 * @throws QueryException
	 */
	public function testRun(): void {
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

	/**
	 * @throws InitException
	 * @throws QueryException
	 * @throws ReflectionException
	 */
	public function testGetFilesForScan(): void {
		$scannerFactory = new ScannerMock(
			new ConfigMock(
				$this->container->query('CoreConfig'),
				$this->container->query('ServerContainer')->getLicenseManager(),
				$this->container->query('ServerContainer')->getLogger()
			),
			$this->container->query('Logger'),
			$this->container->query(IL10N::class)
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
		self::assertInstanceOf(Statement::class, $result);
	}
}
