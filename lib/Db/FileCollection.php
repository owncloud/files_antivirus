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

namespace OCA\Files_Antivirus\Db;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use OCP\IDBConnection;
use OCP\Files\IMimeTypeLoader;

class FileCollection {
	/** @var IDBConnection */
	private $dbConnection;

	/** @var IMimeTypeLoader */
	private $mimeTypeLoader;

	/**
	 * FileCollection constructor.
	 *
	 * @param IDBConnection $dbConnection
	 * @param IMimeTypeLoader $mimeTypeLoader
	 */
	public function __construct(IDBConnection $dbConnection, IMimeTypeLoader $mimeTypeLoader) {
		$this->dbConnection = $dbConnection;
		$this->mimeTypeLoader = $mimeTypeLoader;
	}

	/**
	 * @param int $fileSizeLimit
	 *
	 * @return \Doctrine\DBAL\Driver\Statement|int
	 */
	public function getCollection($fileSizeLimit) {
		$dirMimeTypeId = $this->mimeTypeLoader->getId(
			'httpd/unix-directory'
		);
		$qb = $this->dbConnection->getQueryBuilder();
		if ($this->dbConnection->getDatabasePlatform() instanceof MySqlPlatform) {
			$concatFunction = $qb->createFunction(
				"CONCAT('/', mnt.user_id, '/')"
			);
		} else {
			$concatFunction = $qb->createFunction(
				"'/' || " . $qb->getColumnName('mnt.user_id') . " || '/'"
			);
		}

		if ($fileSizeLimit === -1) {
			$sizeLimitExpr = $qb->expr()->neq('fc.size', $qb->expr()->literal('0'));
		} else {
			$sizeLimitExpr = $qb->expr()->andX(
				$qb->expr()->neq('fc.size', $qb->expr()->literal('0')),
				$qb->expr()->lt('fc.size', $qb->expr()->literal((string) $fileSizeLimit))
			);
		}

		$qb->select(['fc.fileid', 'fc.etag', 'mnt.user_id'])
			->from('filecache', 'fc')
			->leftJoin(
				'fc',
				'files_antivirus',
				'fa',
				$qb->expr()->eq('fa.fileid', 'fc.fileid')
			)
			->innerJoin(
				'fc',
				'mounts',
				'mnt',
				$qb->expr()->andX(
					$qb->expr()->eq('fc.storage', 'mnt.storage_id'),
					$qb->expr()->eq('mnt.mount_point', $concatFunction)
				)
			)
			->where(
				$qb->expr()->neq('fc.mimetype', $qb->expr()->literal($dirMimeTypeId))
			)
			->andWhere(
				$qb->expr()->orX(
					$qb->expr()->isNull('fa.etag'),
					$qb->expr()->neq('fc.etag', 'fa.etag')
				)
			)
			->andWhere(
				$qb->expr()->like('fc.path', $qb->expr()->literal('files/%'))
			)
			->andWhere($sizeLimitExpr)
			->orderBy('fa.check_time', 'ASC')
			->orderBy('fc.storage', 'ASC');
		return $qb->execute();
	}
}
