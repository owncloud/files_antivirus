<?php
/**
 * Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Migrations;
use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/**
 * Creates initial schema
 */
class Version20170808220321 implements ISchemaMigration {
	/**
	 * @param Schema $schema
	 * @param array $options
	 *
	 * @return void
	 */
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if (!$schema->hasTable("{$prefix}files_antivirus")) {
			$table = $schema->createTable("{$prefix}files_antivirus");
			$table->addColumn(
				'fileid',
				'bigint',
				[
					'unsigned' => true,
					'notnull' => true,
					'length' => 11,
				]
			);

			$table->addColumn(
				'check_time',
				'integer',
				[
					'notnull' => true,
					'unsigned' => true,
					'default' => 0,
				]
			);
			$table->setPrimaryKey(['fileid']);
		}

		if (!$schema->hasTable("{$prefix}files_avir_status")) {
			$table = $schema->createTable("{$prefix}files_avir_status");
			$table->addColumn(
				'id',
				'integer',
				[
					'autoincrement' => true,
					'unsigned' => true,
					'notnull' => true,
					'length' => 11,
				]
			);

			$table->addColumn(
				'group_id',
				'integer',
				[
					'notnull' => true,
					'unsigned' => true,
					'default' => 0,
				]
			);

			$table->addColumn(
				'status_type',
				'integer',
				[
					'notnull' => true,
					'unsigned' => true,
					'default' => 0,
				]
			);

			$table->addColumn(
				'result',
				'integer',
				[
					'notnull' => true,
					'unsigned' => false,
					'default' => 0,
				]
			);

			$table->addColumn(
				'match',
				'string',
				[
					'length' => 64,
					'notnull' => false,
					'default' => null,
				]
			);

			$table->addColumn(
				'description',
				'string',
				[
					'length' => 64,
					'notnull' => false,
					'default' => null,
				]
			);

			$table->addColumn(
				'status',
				'integer',
				[
					'length' => 4,
					'notnull' => true,
					'default' => 0,
					'unsigned' => false,
				]
			);
			$table->setPrimaryKey(['id']);
		}
	}
}
