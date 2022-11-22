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
	/**
	 * @var string
	 */
	private $filename;

	public function __construct(string $filename, $resource, $chunkSize) {
		$this->resource = $resource;
		$this->chunkSize = $chunkSize;
		$this->filename = $filename;
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

	public function getFilename(): string {
		return $this->filename;
	}
}
