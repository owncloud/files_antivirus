<?php
/**
 * Copyright (c) 2014 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\Db\RuleMapper;
use OCA\Files_Antivirus\Item;
use OCA\Files_Antivirus\Scanner\InitException;
use OCA\Files_Antivirus\ScannerFactory;
use OCA\Files_Antivirus\Status;
use OCA\Files_Antivirus\Tests\unit\TestBase;
use OCP\AppFramework\QueryException;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OC\Files\View;

class LocalTest extends TestBase {
	public const TEST_CLEAN_FILENAME = 'foo.txt';
	public const TEST_INFECTED_FILENAME = 'kitten.inf';

	/**
	 * @var RuleMapper
	 */
	protected $ruleMapper;
	protected $view;

	/**
	 * @var Item
	 */
	protected $cleanItem;
	/**
	 * @var Item
	 */
	protected $infectedItem;
	/**
	 * @var ScannerFactory
	 */
	protected $scannerFactory;

	/**
	 * @throws InitException
	 * @throws NotFoundException
	 * @throws QueryException
	 * @throws \Exception
	 */
	public function setUp(): void {
		parent::setUp();
		$this->view = $this->getMockBuilder(View::class)
				->disableOriginalConstructor()
				->getMock()
		;
		
		$this->view->method('getOwner')->willReturn('Dummy');
		$this->view->method('file_exists')->willReturn(true);
		$this->view->method('filesize')->willReturn(42);
		
		$this->cleanItem = new Item($this->l10n, $this->view, self::TEST_CLEAN_FILENAME, 42);
		$this->infectedItem = new Item($this->l10n, $this->view, self::TEST_INFECTED_FILENAME, 42);

		$this->ruleMapper = new RuleMapper($this->db);
		$this->ruleMapper->deleteAll();
		$this->ruleMapper->populate();

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
	 * @throws QueryException
	 */
	public function testWrongAntivirusPath(): void {
		$this->expectException(InitException::class);

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$config->method('__call')
			->willReturnCallback(function ($methodName) {
				switch ($methodName) {
					case 'getAvPath':
						return __DIR__ . '/../util/wrong_av_path.sh';
					case 'getAvMode':
						return 'executable';
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
	
	public function testCleanFile(): void {
		$handle = \fopen($this->getTestDataDirItem('foo.txt'), 'rb');
		$this->view->method('fopen')->willReturn($handle);
		self::assertTrue($this->cleanItem->isValid());
		
		$scanner = $this->scannerFactory->getScanner();
		
		$scanner->scan($this->cleanItem);
		$cleanStatus = $scanner->getStatus();
		self::assertEquals(Status::SCANRESULT_CLEAN, $cleanStatus->getNumericStatus());
	}

	/**
	 * @throws NotFoundException
	 * @throws QueryException
	 */
	public function testNotExisting(): void {
		$this->expectException('RuntimeException');
		
		$fileView = new View('');
		$nonExistingItem = new Item($this->l10n, $fileView, 'non-existing.file', 42);
		$scanner = $this->scannerFactory->getScanner();
		$scanner->scan($nonExistingItem);
		$unknownStatus = $scanner->scan($nonExistingItem);
		self::assertEquals(Status::SCANRESULT_UNCHECKED, $unknownStatus->getNumericStatus());
	}
	
	public function testInfected(): void {
		$handle = \fopen($this->getTestDataDirItem('kitten.inf'), 'rb');
		$this->view->method('fopen')->willReturn($handle);
		self::assertTrue($this->infectedItem->isValid());
		$scanner = $this->scannerFactory->getScanner();
		$scanner->scan($this->infectedItem);
		$infectedStatus = $scanner->getStatus();
		self::assertEquals(Status::SCANRESULT_INFECTED, $infectedStatus->getNumericStatus());
	}
}
