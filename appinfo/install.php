<?php
/**
 * Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2014-2018
 * @license AGPL-3.0
 */

\OC::$server->getConfig()->setAppValue(
	'files_antivirus',
	'av_path',
	'/usr/bin/clamscan'
);
