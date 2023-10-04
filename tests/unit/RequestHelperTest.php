<?php
/**
 * Copyright (c) 2018 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OC\Files\Storage\Storage;
use OCA\DAV\Upload\FutureFile;
use OCA\Files_Antivirus\AppInfo\Application;
use OCA\Files_Antivirus\Dav\AntivirusPlugin;
use OCA\Files_Antivirus\RequestHelper;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;

class RequestHelperTest extends TestBase {
	/**
	 * @var IRequest | MockObject
	 */
	private $request;

	/**
	 * @var Storage | MockObject
	 */
	private $storage;

	/**
	 * @var string
	 */
	private $owner = 'anon';

	public function setUp(): void {
		parent::setUp();
		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();

		$this->storage = $this->getMockBuilder(Storage::class)
			->disableOriginalConstructor()
			->getMock();
		$this->storage
			->method('getOwner')
			->willReturn($this->owner);
	}

	public function testPublicUploadSize(): void {
		$size = '100';
		$this->request
			->method('getMethod')
			->willReturn('PUT');

		$this->request
			->method('getHeader')
			->with('CONTENT_LENGTH')
			->willReturn($size);

		$this->request
			->method('getScriptName')
			->willReturn('/somepath/public.php');

		$requestHelper = new RequestHelper($this->request);

		$uploadSize = $requestHelper->getUploadSize(
			$this->storage,
			'/dummy'
		);
		$this->assertEquals($size, $uploadSize);
	}

	public function testChunkSkipped(): void {
		$this->request
			->method('getMethod')
			->willReturn('PUT');

		$this->request
			->method('getScriptName')
			->willReturn('/somepath/remote.php');

		$requestHelper = new RequestHelper($this->request);

		$uploadSize = $requestHelper->getUploadSize(
			$this->storage,
			'uploads/dummy'
		);
		$this->assertNull($uploadSize);
	}

	/**
	 * @throws NotFound
	 */
	public function testCachedSize(): void {
		$davPath = "files/$this->owner/fileName";
		$movePath = "files/fileName";
		$size = 1500;

		$this->request
			->method('getMethod')
			->willReturn('MOVE');

		$node = $this->getMockBuilder(FutureFile::class)
			->disableOriginalConstructor()
			->getMock();
		$node
			->method('getSize')
			->willReturn($size);

		$tree = $this->createMock(Tree::class);
		$tree
			->method('getNodeForPath')
			->willReturn($node);

		$server = $this->createMock(Server::class);
		$server->tree = $tree;

		$user =$this->createMock(IUser::class);
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$logger = $this->createMock(ILogger::class);

		$plugin = new AntivirusPlugin(new Application(), $userSession, $logger);
		$plugin->initialize($server);
		$plugin->beforeMove('/something/.file', $davPath);

		$requestHelper = new RequestHelper($this->request);
		$requestHelper->setSizeForPath($davPath, $size);
		$uploadSize = $requestHelper->getUploadSize(
			$this->storage,
			$movePath
		);
		$this->assertEquals($size, $uploadSize);
	}
}
