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
use \OCP\IRequest;
use Sabre\DAV\Tree;

class RequestHelperTest extends TestBase {
	/**
	 * @var IRequest | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $request;

	/**
	 * @var \OC\Files\Storage\Storage | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $storage;

	/**
	 * @var string
	 */
	private $owner = 'anon';

	public function setUp() {
		parent::setUp();
		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();

		$this->storage = $this->getMockBuilder(Storage::class)
			->disableOriginalConstructor()
			->getMock();
		$this->storage->expects($this->any())
			->method('getOwner')
			->willReturn($this->owner);
	}

	public function testPublicUploadSize() {
		$size = 100;
		$this->request->expects($this->any())
			->method('getMethod')
			->willReturn('PUT');

		$this->request->expects($this->any())
			->method('getHeader')
			->with('CONTENT_LENGTH')
			->willReturn($size);

		$this->request->expects($this->any())
			->method('getScriptName')
			->willReturn('/somepath/public.php');

		$requestHelper = new RequestHelper($this->request);

		$uploadSize = $requestHelper->getUploadSize(
			$this->storage,
			'/dummy'
		);
		$this->assertEquals($size, $uploadSize);
	}

	public function testChunkSkipped() {
		$this->request->expects($this->any())
			->method('getMethod')
			->willReturn('PUT');

		$this->request->expects($this->any())
			->method('getScriptName')
			->willReturn('/somepath/remote.php');

		$requestHelper = new RequestHelper($this->request);

		$uploadSize = $requestHelper->getUploadSize(
			$this->storage,
			'uploads/dummy'
		);
		$this->assertNull($uploadSize);
	}

	public function testCachedSize() {
		$davPath = "files/$this->owner/fileName";
		$movePath = "files/fileName";
		$size = 1500;

		$this->request->expects($this->any())
			->method('getMethod')
			->willReturn('MOVE');

		$node = $this->getMockBuilder(FutureFile::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getSize')
			->willReturn($size);

		$tree = $this->createMock(Tree::class);
		$tree->expects($this->any())
			->method('getNodeForPath')
			->willReturn($node);

		$server = $this->createMock(\Sabre\DAV\Server::class);
		$server->tree = $tree;

		$plugin = new AntivirusPlugin(new Application());
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
