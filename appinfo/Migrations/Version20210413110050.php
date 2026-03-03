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
 * Cleans table before adding etag field
 */
class Version20210413110050 implements ISqlMigration {
	public function sql(IDBConnection $conn) {
		$conf = \OC::$server->getConfig();
		$query = 'SELECT `configkey`, `configvalue` FROM `*PREFIX*appconfig` WHERE `appid` = \'files_antivirus\' AND (`configkey` = \'av_path\' OR `configkey` = \'av_cmd_options\')';
		$result = $conn->executeQuery($query);
		while ($row = $result->fetchAssociative()) {
			try {
				$conf->setSystemValue('files_antivirus.' . $row['configkey'], $row['configvalue']);
			} catch (\Exception $e) {
				echo 'Migration failed: ', $e->getMessage(), '\n';
				return [];
			}
		}
		$result->free();

		$query = 'DELETE FROM `*PREFIX*appconfig` WHERE `appid` = \'files_antivirus\' AND (`configkey` = \'av_path\' OR `configkey` = \'av_cmd_options\')';
		$conn->executeStatement($query);

		return [];
	}
}
