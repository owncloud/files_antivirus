<?php
/**
 * ownCloud - files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2015-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus;

class Content implements IScannable {
	/**
	 * @var string
	 */
	protected $content;
	/**
	 * @var int
	 */
	protected $currentPosition = 0;
	/**
	 * @var int
	 */
	protected $chunkSize;
	/**
	 * @var string
	 */
	private $filename;

	public function __construct(string $filename, string $content, int $chunkSize) {
		$this->content = $content;
		$this->chunkSize = $chunkSize;
		$this->filename = $filename;
	}
	
	public function fread() {
		if ($this->currentPosition >= \strlen($this->content)) {
			return false;
		}
		$chunk = \substr($this->content, $this->currentPosition, $this->chunkSize);
		$this->currentPosition += $this->chunkSize;
		
		return $chunk;
	}

	public function getFilename(): string {
		return $this->filename;
	}
}
