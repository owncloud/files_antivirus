<?php
/**
 * Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Tests\unit\Migrations;

use Doctrine\DBAL\Driver\Statement;
use OCA\Files_Antivirus\Migrations\Version20210413110050;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;

// migrations live in appinfo/Migrations and are loaded by core's MigrationService
// via a file scan, not through the app's PSR-4 autoloader, so require it directly.
// Guard against a redeclare if the class is already loaded in the same process.
if (!\class_exists(Version20210413110050::class, false)) {
	require_once __DIR__ . '/../../../appinfo/Migrations/Version20210413110050.php';
}

class Version20210413110050Test extends TestCase {
	/**
	 * av_path and av_cmd_options are read from config.php only, so the migration
	 * must only delete the appconfig rows. It must not read them or write them to
	 * system config: the previous version did, overwriting the values set in
	 * config.php. Asserting that the only query is the DELETE pins this without
	 * a live database or a writable config.php.
	 */
	public function testMigrationOnlyDeletesRows(): void {
		$statement = $this->createMock(Statement::class);
		$statement->expects(self::once())->method('closeCursor');
		$connection = $this->createMock(IDBConnection::class);
		$connection->expects(self::once())
			->method('executeQuery')
			->with(self::callback(static function ($query) {
				return \is_string($query)
					&& \stripos($query, 'DELETE') === 0
					&& \strpos($query, 'av_path') !== false
					&& \strpos($query, 'av_cmd_options') !== false;
			}))
			->willReturn($statement);

		$migration = new Version20210413110050();

		self::assertNull($migration->sql($connection));
	}
}
