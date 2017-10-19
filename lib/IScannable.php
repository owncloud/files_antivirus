<?php
/**
 * Copyright (c) 2014 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus;

interface IScannable {
	/**
	 * Return av_chunk_size bytes of something
	 * or false when there is no more bytes left
	 */
	public function fread();
}
