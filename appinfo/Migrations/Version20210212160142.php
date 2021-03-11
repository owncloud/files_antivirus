<?php
/**
 * Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2021
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/**
 * Moved here from preupdate.php. Migrates a long table name into a shorter one.
 * Needs to be done for app versions below 0.6.1
 */
class Version20210212160142 implements ISchemaMigration {
	/**
	 * @param Schema $schema
	 * @param array $options
	 *
	 * @return void
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];

		if (
			$schema->hasTable("${prefix}files_antivirus_status")
			&& $schema->hasTable("${prefix}files_avir_status") === false
		) {
			$dbConn = \OC::$server->getDatabaseConnection();
			$alterQuery = $dbConn->prepare(
				'ALTER TABLE `*PREFIX*files_antivirus_status` RENAME TO `*PREFIX*files_avir_status`'
			);
			$alterQuery->execute();
		}
	}
}
