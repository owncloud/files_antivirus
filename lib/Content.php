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
	protected $content;
	
	protected $currentPosition = 0;
	
	protected $chunkSize;
	
	public function __construct($content, $chunkSize) {
		$this->content = $content;
		$this->chunkSize = $chunkSize;
	}
	
	public function fread() {
		if ($this->currentPosition >= \strlen($this->content)) {
			return false;
		}
		$chunk = \substr($this->content, $this->currentPosition, $this->chunkSize);
		$this->currentPosition = $this->currentPosition + $this->chunkSize;
		
		return $chunk;
	}
}
