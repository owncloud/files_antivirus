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

use OCP\Migration\ISimpleMigration;
use OCP\Migration\IOutput;

/**
 * Moved here from update.php
 * This migration removes outdated cron jobs and registers a new one
 */
class Version20210212155302 implements ISimpleMigration {
	/**
	 * @param IOutput $out
	 */
	public function run(IOutput $out) {
		$jobList = \OC::$server->getJobList();
		// An ancient job, added even before namespaces are introduced
		$jobList->remove('OC_Files_Antivirus_BackgroundScanner', null);
		// An old job
		$jobList->remove('OCA\Files_Antivirus\BackgroundScanner', null);
		$jobList->add('OCA\Files_Antivirus\Cron\Task');
	}
}
