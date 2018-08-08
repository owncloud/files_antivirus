<?php
/**
 * ownCloud
 *
 * @author Phil Davis <phil@jankaritech.com>
 * @copyright Copyright (c) 2018 Phil Davis phil@jankaritech.com
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use TestHelpers\AppConfigHelper;
use TestHelpers\SetupHelper;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

require_once 'bootstrap.php';

/**
 * Defines test steps specific to the antivirus app
 */
class AntivirusContext implements Context {
	use Logging;

	/**
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * @When /^the administrator (enables|disables) the files_antivirus app$/
	 * @Given /^the administrator (has enabled|has disabled) the files_antivirus app$/
	 *
	 * @param string $enableDisable
	 *
	 * @return void
	 */
	public function theAdministratorEnablesTheAntivirusApp($enableDisable) {
		if (($enableDisable === "enables") || ($enableDisable === "has enabled")) {
			$this->featureContext->invokingTheCommand("app:enable files_antivirus");
		} else {
			$this->featureContext->invokingTheCommand("app:disable files_antivirus");
		}
	}

	/**
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 */
	public function setUpScenario(BeforeScenarioScope $scope) {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
		SetupHelper::init(
			$this->featureContext->getAdminUsername(),
			$this->featureContext->getAdminPassword(),
			$this->featureContext->getBaseUrl(),
			$this->featureContext->getOcPath()
		);
	}

	/**
	 * @AfterScenario
	 *
	 * @return void
	 */
	public function tearDownScenario() {
		AppConfigHelper::modifyServerConfigs(
			$this->featureContext->getBaseUrl(),
			$this->featureContext->getAdminUsername(),
			$this->featureContext->getAdminPassword(),
			[['appid' => 'files_antivirus', 'configkey' => 'av_max_file_size', 'value'=>'-1']],
			2
		);
	}
}
