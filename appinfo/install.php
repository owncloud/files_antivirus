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

$passed = \OC::$server->getConfig()->getAppValue('files_antivirus', 'autoprobe', false);
if ($passed === false) {
	$app = new \OCA\Files_Antivirus\AppInfo\Application();
	$app->autoProbe();
}
