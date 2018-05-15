<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2015-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Cron;

use OC\BackgroundJob\TimedJob;
use OCA\Files_Antivirus\AppInfo\Application;


class Task extends TimedJob {

	/**
	 * sets the correct interval for this timed job
	 */
	public function __construct() {
		// Run once per 15 minutes
		$this->setInterval(60 * 15);
	}

	/**
	 * @param string $argument
	 *
	 * @return void
	 */
	protected function run($argument) {
		if (!\OCP\App::isEnabled('files_antivirus')) {
			return;
		}

		$application = new Application();
		$container = $application->getContainer();
		$container->query('BackgroundScanner')->run();
	}
}
