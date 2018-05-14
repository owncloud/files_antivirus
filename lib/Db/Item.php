<?php
/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class Item
 *
 * @package OCA\Files_Antivirus\Db
 */
class Item extends Entity {
	/**
	 * fileid that was scanned
	 *
	 * @var int
	 */
	protected $fileid;
	
	/**
	 * Timestamp of the check
	 *
	 * @var int
	 */
	protected $checkTime;
	
}
