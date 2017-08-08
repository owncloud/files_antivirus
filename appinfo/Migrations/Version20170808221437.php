<?php

namespace OCA\Files_Antivirus\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

/** Updates some fields to bigint if required */
class Version20170808221437 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];

		if ($schema->hasTable("${prefix}files_antivirus")) {
			$table = $schema->getTable("{$prefix}files_antivirus");

			$fileIdColumn = $table->getColumn('fileid');
			if ($fileIdColumn && $fileIdColumn->getType()->getName() !== Type::BIGINT) {
				$fileIdColumn->setType(Type::getType(Type::BIGINT));
				$fileIdColumn->setOptions(['length' => 20]);
			}
		}
	}
}
