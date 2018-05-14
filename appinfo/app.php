<?php
/**
 * ownCloud - Files_antivirus
 *
 * @author Manuel Deglado <manuel.delgado@ucr.ac.cr>
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright 2012 Manuel Deglado manuel.delgado@ucr.ac.cr
 * @copyright 2014-2018 Viktar Dubiniuk
 * @license AGPL-3.0
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

$app = new \OCA\Files_Antivirus\AppInfo\Application();
OCP\Util::connectHook('OC_Filesystem', 'preSetup', $app, 'setupWrapper');

\OC::$server->getActivityManager()->registerExtension(
	function () {
		return new \OCA\Files_Antivirus\Activity(
			\OC::$server->query('L10NFactory'),
			\OC::$server->getURLGenerator()
		);
	}
);
