<?php

/**
 * Copyright (c) 2016 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OCA\Files_Antivirus\Tests\unit;

use OC\Files\Storage\Temporary;
use OCA\Files_Antivirus\AvirWrapper;
use OCA\Files_Antivirus\RequestHelper;
use OCA\Files_Antivirus\Tests\util\DummyClam;
use Test\Util\User\Dummy;


class AvirWrapperTest extends TestBase {
	
	const UID = 'testo';
	const PWD = 'test';

	protected $scannerFactory;

	protected $requestHelper;

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

		$this->scannerFactory = new Mock\ScannerFactory(
			new Mock\Config($this->container->query('CoreConfig')),
			$this->container->query('Logger')
		);

		$this->requestHelper = $this->getMockBuilder(RequestHelper::class)
			->disableOriginalConstructor()
			->getMock();

		$this->requestHelper->expects($this->any())
			->method('getUploadSize')
			->will($this->returnValue(1));

		\OC::$server->getUserSession()->login(self::UID, self::PWD);
		\OC::$server->getSession()->set('user_id', self::UID);
		\OC::$server->getUserFolder(self::UID);
	}

	/**
	 * @expectedException \OCP\Files\FileContentNotAllowedException
	 */
	public function testInfectedFwrite(){
		$wrapper = $this->getWrapper();
		$fd = $wrapper->fopen('killing bee', 'w+');
		@fwrite($fd, 'it ' . DummyClam::TEST_SIGNATURE);
		@fclose($fd);
	}

	/**
	 * @expectedException \OCP\Files\FileContentNotAllowedException
	 */
	public function testBigInfectedFwrite(){
		$wrapper = $this->getWrapper();
		$fd = $wrapper->fopen('killing whale', 'w+');
		@fwrite($fd, str_repeat('0', DummyClam::TEST_STREAM_SIZE-2) . DummyClam::TEST_SIGNATURE );
		@fwrite($fd, DummyClam::TEST_SIGNATURE);
		@fclose($fd);
	}

	/**
	 * @expectedException \OCP\Files\ForbiddenException
	 */
	public function testInfectedFilePutContents(){
		$wrapper = $this->getWrapper();
		$wrapper->file_put_contents('test_put_infected','it ' . DummyClam::TEST_SIGNATURE);
	}

	public function testHealthFilePutContents(){
		$wrapper = $this->getWrapper();
		$result = $wrapper->file_put_contents('test_put_healthly','it works!');
		$this->assertNotFalse($result);
	}

	private function getWrapper() {
		$storage = new Temporary([]);
		$wrapper = new AvirWrapper([
			'storage' => $storage,
			'appConfig' => $this->config,
			'scannerFactory' => $this->scannerFactory,
			'l10n' => $this->l10n,
			'logger' => $this->container->query('Logger'),
			'requestHelper' => $this->requestHelper,
		]);
		return $wrapper;
	}

	public static function tearDownAfterClassClass() {
		parent::tearDownAfterClass();
		\OC::$server->getUserManager()->get(self::UID)->delete();
		\OC_User::clearBackends();
	}
}
