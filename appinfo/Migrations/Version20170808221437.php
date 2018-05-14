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
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

/**
 * Updates some fields to bigint if required
 */
class Version20170808221437 implements ISchemaMigration {
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

		if ($schema->hasTable("${prefix}files_antivirus")) {
			$table = $schema->getTable("{$prefix}files_antivirus");

			$fileIdColumn = $table->getColumn('fileid');
			if ($fileIdColumn
				&& $fileIdColumn->getType()->getName() !== Type::BIGINT
			) {
				$fileIdColumn->setType(Type::getType(Type::BIGINT));
				$fileIdColumn->setOptions(['length' => 20]);
			}
		}
	}
}
