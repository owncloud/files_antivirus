<?php

/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OCA\Files_Antivirus\AppInfo\Application;
use OCP\AppFramework\IAppContainer;
use OCP\IDb;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use OCA\Files_Antivirus\AppConfig;
use OCP\IL10N;

abstract class TestBase extends TestCase {
	/**
	 * @var IDb
	 */
	protected $db;
	/**
	 * @var Application
	 */
	protected $application;
	/**
	 * @var IAppContainer|\OC\AppFramework\DependencyInjection\DIContainer
	 */
	protected $container;
	/**
	 * @var AppConfig|MockObject
	 */
	protected $config;
	/**
	 * @var IL10N|MockObject
	 */
	protected $l10n;

	/**
	 * @throws \Exception
	 */
	public function setUp(): void {
		parent::setUp();
		\OC_App::enable('files_antivirus');
		
		$this->db = \OC::$server->getDb();
		
		$this->application = new Application();
		$this->container = $this->application->getContainer();
		
		$this->config = $this->getMockBuilder(AppConfig::class)
				->disableOriginalConstructor()
				->getMock()
		;
		$this->config->method('__call')
			->willReturnCallback([$this, 'getAppValue']);
		$this->config->method('getAvChunkSize')
			->willReturn(8192);

		$this->l10n = $this->getMockBuilder(IL10N::class)
				->disableOriginalConstructor()
				->getMock()
		;
		$this->l10n->method('t')->will($this->returnArgument(0));
	}

	public function getAppValue($methodName) {
		switch ($methodName) {
			case 'getAvPath':
				return  __DIR__ . '/../util/avir.sh';
			case 'getAvMode':
				return 'executable';
			case 'getAvMaxFileSize':
				return -1;
		}
	}

	protected function getTestDataDirItem($relativePath): string {
		return __DIR__ . '/../data/' . $relativePath;
	}
}
