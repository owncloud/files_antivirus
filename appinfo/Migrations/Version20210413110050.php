<?php
/**
 * Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author David Christofas <dchristofas@owncloud.com>
 *
 * @copyright David Christofas 2021
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Migrations;

use OCP\IDBConnection;
use OCP\Migration\ISqlMigration;

/**
 * Removes the obsolete av_path and av_cmd_options rows from appconfig. Both keys
 * are read from config.php only (see AppConfig), so leftover appconfig values are
 * dropped, not copied into system config.
 */
class Version20210413110050 implements ISqlMigration {
	public function sql(IDBConnection $conn) {
		$query = 'DELETE FROM `*PREFIX*appconfig` WHERE `appid` = \'files_antivirus\' AND (`configkey` = \'av_path\' OR `configkey` = \'av_cmd_options\')';
		$conn->executeStatement($query);

		return [];
	}
}
