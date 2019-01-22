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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

/**
 * Add etag field to the table
 */
class Version20180116142011 implements ISchemaMigration {
	/**
	 * @param Schema $schema
	 * @param array $options
	 *
	 * @return void
	 */
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		$table = $schema->getTable("{$prefix}files_antivirus");
		$table->addColumn(
			'etag',
			Type::STRING,
			[
				'default' => null,
				'length' => 40,
				'notnull' => false
			]
		);
	}
}
