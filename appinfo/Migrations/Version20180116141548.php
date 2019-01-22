<?php
/**
 * Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2019
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Migrations;
use OCP\IDBConnection;
use OCP\Migration\ISqlMigration;

/**
 * Cleans table before adding etag field
 */
class Version20180116141548 implements ISqlMigration {
	/**
	 * @param IDBConnection $connection
	 * @return void
	 */
	public function sql(IDBConnection $connection) {
		$sql = $connection->getDatabasePlatform()->getTruncateTableSQL(
			"`*PREFIX*files_antivirus`"
		);
		$connection->executeUpdate($sql);
	}
}
