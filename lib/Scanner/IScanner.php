<?php
/**
 * ownCloud - Files_antivirus
 *
 * @author Manuel Deglado <manuel.delgado@ucr.ac.cr>
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright 2012 Manuel Deglado manuel.delgado@ucr.ac.cr
 * @copyright 2014-2021 Viktar Dubiniuk
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

namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\IScannable;
use OCA\Files_Antivirus\Status;
use OCP\IL10N;
use OCP\ILogger;

interface IScanner {
	public function __construct(AppConfig $config, ILogger $logger, IL10N $l10n);

	/**
	 * Synchronous scan
	 */
	public function scan(IScannable $item): Status;

	/**
	 * Get write handle here.
	 * Do NOT open connection in constructor because this method
	 * is used for reconnection
	 */
	public function initScanner(string $fileName): void;

	public function onAsyncData($data): void;

	/**
	 * Async scan - resource is closed
	 */
	public function completeAsyncScan(): Status;

	public function shutdownScanner(): void;
}
