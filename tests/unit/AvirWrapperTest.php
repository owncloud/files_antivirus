<?php
/**
 * Copyright (c) 2016 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OC\Files\Storage\Storage;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Wrapper;
use OCA\Files_Antivirus\AvirWrapper;
use OCA\Files_Antivirus\RequestHelper;
use OCA\Files_Antivirus\Tests\util\DummyClam;
use OCP\IL10N;
use Test\Util\User\Dummy;

class AvirWrapperTest extends TestBase {
	public const UID = 'testo';
	public const PWD = 'test';

	/**
	 * @var  Mock\ScannerFactory
	 */
	protected $scannerFactory;

	/**
	 * @var RequestHelper
	 */
	protected $requestHelper;

	/**
	 * @var string
	 */
	protected $skeletonDirectory;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		\OC_User::clearBackends();
		\OC_User::useBackend(new Dummy());
	}

	public function setUp(): void {
		parent::setUp();
		$this->skeletonDirectory = \OC::$server->getConfig()->getSystemValue(
			'skeletondirectory',
			null
		);
		\OC::$server->getConfig()->setSystemValue(
			'skeletondirectory',
			''
		);
		if (!\OC::$server->getUserManager()->get(self::UID)) {
			\OC::$server->getUserManager()->createUser(self::UID, self::PWD);
		}

		$this->scannerFactory = new Mock\ScannerFactory(
			new Mock\Config($this->container->query('CoreConfig')),
			$this->container->query('Logger'),
			$this->container->query(IL10N::class)
		);

		$this->requestHelper = $this->getMockBuilder(RequestHelper::class)
			->disableOriginalConstructor()
			->getMock();

		$this->requestHelper
			->method('getUploadSize')
			->willReturn(1);

		\OC::$server->getUserSession()->login(self::UID, self::PWD);
		\OC::$server->getSession()->set('user_id', self::UID);
		\OC::$server->getUserFolder(self::UID);
	}

	/**
	 */
	public function testInfectedFwrite() {
		$this->expectException(\OCP\Files\FileContentNotAllowedException::class);

		$wrapper = $this->getWrapper();
		$fd = $wrapper->fopen('killing bee', 'w+');
		@\fwrite($fd, 'it ' . DummyClam::TEST_SIGNATURE);
		@\fclose($fd);
	}

	/**
	 */
	public function testBigInfectedFwrite() {
		$this->expectException(\OCP\Files\FileContentNotAllowedException::class);

		$wrapper = $this->getWrapper();
		$fd = $wrapper->fopen('killing whale', 'w+');
		@\fwrite($fd, \str_repeat('0', DummyClam::TEST_STREAM_SIZE-2) . DummyClam::TEST_SIGNATURE);
		@\fwrite($fd, DummyClam::TEST_SIGNATURE);
		@\fclose($fd);
	}

	/**
	 */
	public function testInfectedFilePutContents() {
		$this->expectException(\OCP\Files\ForbiddenException::class);

		$wrapper = $this->getWrapper();
		$wrapper->file_put_contents('test_put_infected', 'it ' . DummyClam::TEST_SIGNATURE);
	}

	public function testHealthFilePutContents() {
		$wrapper = $this->getWrapper();
		$result = $wrapper->file_put_contents('test_put_healthly', 'it works!');
		self::assertNotFalse($result);
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

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		\OC::$server->getUserManager()->get(self::UID)->delete();
		\OC_User::clearBackends();
	}

	public function testUnlink() {
		$storage = $this->createMock(Storage::class);
		$storage->expects(self::once())
			->method('unlink')
			->willReturn(true);
		$wrapper = $this->createMock(Wrapper::class);
		$wrapper->method('unlink')->willReturn(false);
		$wrapper->method('getWrapperStorage')->willReturn($storage);
		$avirWrapper = $this->getMockBuilder(AvirWrapper::class)
			->disableOriginalConstructor()
			->setMethods(['getWrapperStorage'])
		->getMock();
		$avirWrapper->method('getWrapperStorage')
			->willReturn($wrapper);

		$result = $avirWrapper->unlink('/some/infected/path');
		self::assertTrue($result);
	}

	protected function tearDown(): void {
		parent::tearDown();
		if ($this->skeletonDirectory !== null) {
			\OC::$server->getConfig()->setSystemValue(
				'skeletondirectory',
				$this->skeletonDirectory
			);
		}
	}
}
