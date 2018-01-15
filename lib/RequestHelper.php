<?php
/**
 * Copyright (c) 2017 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus;

use \OCP\IRequest;

class RequestHelper {

	/** @var  IRequest */
	private $request;

	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	/**
	 * Get current upload size
	 * returns null for chunks and when there is no upload
	 *
	 * @param string $path
	 * @return int|null
	 */
	public function getUploadSize($path) {
		$uploadSize = null;

		$requestMethod = $this->request->getMethod();
		$isRemoteScript = $this->isScriptName('remote.php');
		$isPublicScript = $this->isScriptName('public.php');
		// Are we uploading anything?
		if (in_array($requestMethod, ['MOVE', 'PUT']) && $isRemoteScript) {
			// v1 && v2 Chunks are not scanned
			if (
				\OC_FileChunking::isWebdavChunk()
				|| ($requestMethod === 'PUT' &&  strpos($path, 'uploads/') === 0)
			) {
				return null;
			}

			if ($requestMethod === 'PUT') {
				$uploadSize = (int)$this->request->getHeader('CONTENT_LENGTH');
			} else {
				$uploadSize = (int)$this->request->getHeader('OC_TOTAL_LENGTH');
			}
		} else if ($requestMethod === 'PUT' && $isPublicScript) {
			$uploadSize = (int)$this->request->getHeader('CONTENT_LENGTH');
		}

		return $uploadSize;
	}

	/**
	 *
	 * @param string $string
	 * @return bool
	 */
	public function isScriptName($string) {
		$pattern = sprintf('|/%s|', preg_quote($string));
		return preg_match($pattern, $this->request->getScriptName()) === 1;
	}
}
