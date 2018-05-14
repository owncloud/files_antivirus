<?php

/**
 * @author Tom Needham <tom@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Antivirus;

use OCA\Files_Antivirus\AppInfo\Application;
use OCA\Files_Antivirus\Controller\SettingsController;
use OCP\Settings\ISettings;

class AdminPanel implements ISettings {

	/**
	 * @var Application
	 */
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	public function getPriority() {
		return 10;
	}

	public function getPanel() {
		return $this->app->getContainer()->query(SettingsController::class)->index();
	}

	public function getSectionID() {
		return 'security';
	}
}
