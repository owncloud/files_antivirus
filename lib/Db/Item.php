<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2015-2018
 * @license AGPL-3.0
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
