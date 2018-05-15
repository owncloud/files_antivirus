<?php
/**
 * Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 *
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Lukas Reschke 2014
 * @copyright Viktar Dubiniuk 2014-2018
 * @license AGPL-3.0
 */

$application = new \OCA\Files_Antivirus\AppInfo\Application();
$application->registerRoutes(
	$this,
	[
		'routes' => [
			['name' => 'rule#listAll', 'url' => 'settings/rule/listall', 'verb' => 'GET'],
			['name' => 'rule#clear', 'url' => 'settings/rule/clear', 'verb' => 'POST'],
			['name' => 'rule#reset', 'url' => 'settings/rule/reset', 'verb' => 'POST'],
			['name' => 'rule#save', 'url' => 'settings/rule/save', 'verb' => 'POST'],
			['name' => 'rule#delete', 'url' => 'settings/rule/delete', 'verb' => 'POST'],
			['name' => 'settings#save', 'url' => 'settings/save', 'verb' => 'POST'],
		]
	]
);
