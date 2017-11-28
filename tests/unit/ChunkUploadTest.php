<?php

/**
 * Copyright (c) 2017 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OCA\Files_Antivirus\Tests\unit;

use OC\Files\Filesystem;
use OC\Files\View;
use OC\Files\Storage\Storage;
use OCA\Files_Antivirus\AvirWrapper;
use Test\Util\User\Dummy;

class ChunkUploadTest extends TestBase{
	const UID = 'testo';
	const PWD = 'test';

	protected $scannerFactory;

	protected $isWrapperRegistered = false;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		\OC_User::clearBackends();
		\OC_User::useBackend(new Dummy());
	}
	
	public function setUp() {
		parent::setUp();
		if (!\OC::$server->getUserManager()->get(self::UID)) {
			\OC::$server->getUserManager()->createUser(self::UID, self::PWD);
		}
		
		$this->scannerFactory = $this->getMockBuilder(\OCA\Files_Antivirus\ScannerFactory::class)
			->setConstructorArgs([
				new Mock\Config($this->container->query('CoreConfig')),
				$this->container->query('Logger')
			])
			->setMethods(['getScanner'])
			->getMock();

		if (!$this->isWrapperRegistered) {
			Filesystem::addStorageWrapper(
				'oc_avir_test_chunk',
				[$this, 'wrapperCallback'],
				3
			);
			$this->isWrapperRegistered = true;
		}
		\OC::$server->getUserSession()->login(self::UID, self::PWD);
		\OC::$server->getSession()->set('user_id', self::UID);
		\OC::$server->getUserFolder(self::UID);
	}

	public function testSkipIndividualChunks(){
		$this->scannerFactory->expects($this->never())
			->method('getScanner');

		$path = '/' . self::UID . '/uploads';
		$rootView = new View();
		if (!$rootView->file_exists($path)) {
			$rootView->mkdir($path);
		}
		$view = new View($path);
		$fd = $view->fopen('/chunk', 'w+');
		@fwrite($fd, 'abcdead');
		@fclose($fd);
	}
	
	public function wrapperCallback($mountPoint, $storage){
		/**
		 * @var Storage $storage
		 */
		if ($storage instanceof Storage) {
			return new AvirWrapper([
				'storage' => $storage,
				'appConfig' => $this->config,
				'scannerFactory' => $this->scannerFactory,
				'l10n' => $this->l10n,
				'logger' => $this->container->query('Logger'),
				'requestHelper' => $this->container->query('RequestHelper'),
			]);
		} else {
			return $storage;
		}
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		Filesystem::getLoader()->removeStorageWrapper('oc_avir_test_chunk');
		\OC::$server->getUserManager()->get(self::UID)->delete();
		\OC_User::clearBackends();
	}
}
