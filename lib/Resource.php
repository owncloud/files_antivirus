<?php
/**
 * ownCloud - files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2021
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus;

class Resource implements IScannable {
	protected $resource;
	protected $chunkSize;

	public function __construct($resource, $chunkSize) {
		$this->resource = $resource;
		$this->chunkSize = $chunkSize;
	}

	/**
	 * @inheritDoc
	 */
	public function fread() {
		if (\feof($this->resource)) {
			return false;
		}
		return \fread($this->resource, $this->chunkSize);
	}
}
