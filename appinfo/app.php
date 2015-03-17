<?php

/**
* ownCloud - files_antivirus
*
* @author Manuel Deglado
* @copyright 2012 Manuel Deglado manuel.delgado@ucr.ac.cr
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

OCP\App::registerAdmin('files_antivirus', 'admin');
OCP\BackgroundJob::AddRegularTask('OCA\Files_Antivirus\Cron\Task', 'run');

$app = new \OCA\Files_Antivirus\AppInfo\Application();
$app->getContainer()->query('FilesystemHooks')->register();

$avBinary = \OCP\Config::getAppValue('files_antivirus', 'av_path', '');
if (empty($avBinary)){
	try {
		$ruleMapper = $app->getContainer()->query('RuleMapper');
		$rules = $ruleMapper->findAll();
		if(!count($rules)) {
			$ruleMapper->populate();
		}
		\OCP\Config::setAppValue('files_antivirus', 'av_path', '/usr/bin/clamscan');
	} catch (\Exception $e) {
	}
}