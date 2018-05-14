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

use OCP\IDb;
use OCP\AppFramework\Db\Mapper;

/**
 * Class ItemMapper
 *
 * @package OCA\Files_Antivirus\Db
 */
class ItemMapper extends Mapper {
	/**
	 * ItemMapper constructor.
	 *
	 * @param IDb $db
	 */
	public function __construct(IDb $db) {
		parent::__construct($db, 'files_antivirus', '\OCA\Files_Antivirus\Db\Item');
	}
}
