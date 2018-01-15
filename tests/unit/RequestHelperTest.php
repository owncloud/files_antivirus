<?php
/**
 * Copyright (c) 2018 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OCA\Files_Antivirus\RequestHelper;
use \OCP\IRequest;

class RequestHelperTest extends TestBase {
	public function testPublicUploadSize() {
		$size = 100;
		$request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->any())
			->method('getMethod')
			->willReturn('PUT');

		$request->expects($this->any())
			->method('getHeader')
			->with('CONTENT_LENGTH')
			->willReturn($size);

		$request->expects($this->any())
			->method('getScriptName')
			->willReturn('/somepath/public.php');

		$requestHelper = new RequestHelper($request);

		$uploadSize = $requestHelper->getUploadSize('/dummy');
		$this->assertEquals($size, $uploadSize);

	}
}
