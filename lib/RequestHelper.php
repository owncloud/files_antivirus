<?php
/**
 * ownCloud - files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2017-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus;

use OC\Cache\CappedMemoryCache;
use OC\Files\Storage\Storage;
use \OCP\IRequest;

/**
 * Used to detect the size of the uploaded file
 *
 * @package OCA\Files_Antivirus
 */
class RequestHelper {
	/**
	 * @var  IRequest
	 */
	private $request;

	/**
	 * @var CappedMemoryCache
	 */
	private static $fileSizeCache;

	/**
	 * RequestHelper constructor.
	 *
	 * @param IRequest $request
	 */
	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	/**
	 * @return CappedMemoryCache
	 */
	public function getCache() {
		if (self::$fileSizeCache === null) {
			self::$fileSizeCache = new CappedMemoryCache();
		}
		return self::$fileSizeCache;
	}

	/**
	 * @param string $path
	 * @param string $size
	 *
	 * @return void
	 */
	public function setSizeForPath($path, $size) {
		$this->getCache()->set($path, $size);
	}

	/**
	 * Get current upload size
	 * returns null for chunks and when there is no upload
	 *
	 * @param Storage $storage
	 * @param string $path
	 *
	 * @return int|null
	 */
	public function getUploadSize(Storage $storage, $path) {
		$requestMethod = $this->request->getMethod();

		// Handle MOVE first
		// the size is cached by the app dav plugin in this case
		if ($requestMethod === 'MOVE') {
			// remove .ocTransferId444531916.part from part files
			$cleanedPath = \preg_replace(
				'|\.ocTransferId\d+\.part$|',
				'',
				$path
			);
			// cache uses dav path in /files/$user/path format
			$translatedPath = \preg_replace(
				'|^files/|',
				'files/' . $storage->getOwner('/') . '/',
				$cleanedPath
			);
			$cachedSize = $this->getCache()->get($translatedPath);
			if ($cachedSize > 0) {
				return $cachedSize;
			}
		}

		// Are we uploading anything?
		if ($requestMethod !== 'PUT') {
			return null;
		}
		$isRemoteScript = $this->isScriptName('remote.php');
		$isPublicScript = $this->isScriptName('public.php');
		if (!$isRemoteScript && !$isPublicScript) {
			return null;
		}

		if ($isRemoteScript) {
			// v1 && v2 Chunks are not scanned
			if (\strpos($path, 'uploads/') === 0) {
				return null;
			}

			if (\OC_FileChunking::isWebdavChunk()
				&& \strpos($path, 'cache/') === 0
			) {
				return null;
			}
		}
		$uploadSize = (int)$this->request->getHeader('CONTENT_LENGTH');

		return $uploadSize;
	}

	/**
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public function isScriptName($string) {
		$pattern = \sprintf('|/%s|', \preg_quote($string));
		return \preg_match($pattern, $this->request->getScriptName()) === 1;
	}
}
