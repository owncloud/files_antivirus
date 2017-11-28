<?php
/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus;

class Content implements IScannable{
	
	protected $content;
	
	protected $currentPosition = 0;
	
	protected $chunkSize;
	
	public function __construct($content, $chunkSize){
		$this->content = $content;
		$this->chunkSize = $chunkSize;
	}
	
	public function fread(){
		if ($this->currentPosition >=  strlen($this->content)) {
			return false;
		}
		$chunk = substr($this->content, $this->currentPosition, $this->chunkSize);
		$this->currentPosition = $this->currentPosition + $this->chunkSize;
		
		return $chunk;
	}
}
