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

require_once 'bootstrap.php';

/**
 * Defines test steps specific to the antivirus app
 */
class AntivirusContext implements Context {

	/**
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * @var PublicWebDavContext
	 */
	private $publicWebDavContext;

	/**
	 * @return string
	 */
	private function getRelativePathToTestDataFolder() {
		$relativePath
			= $this->featureContext->getPathFromCoreToAppAcceptanceTests(__DIR__);
		return "$relativePath/data/";
	}

	/**
	 * @When /^the administrator (enables|disables) the files_antivirus app$/
	 * @Given /^the administrator (has enabled|has disabled) the files_antivirus app$/
	 *
	 * @param string $enableDisable
	 *
	 * @return void
	 * @throws Exception
	 */
	public function theAdministratorEnablesTheAntivirusApp($enableDisable) {
		if (($enableDisable === "enables") || ($enableDisable === "has enabled")) {
			$this->featureContext->runOcc(["app:enable files_antivirus"]);
		} else {
			$this->featureContext->runOcc(["app:disable files_antivirus"]);
		}
	}

	/**
	 * @When user :user uploads file :source from the antivirus test data folder to :destination using the WebDAV API
	 * @Given user :user has uploaded file :source from the antivirus test data folder to :destination
	 *
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 */
	public function userUploadsFileFromAntivirusDataFolderTo(
		$user, $source, $destination
	) {
		$source = $this->getRelativePathToTestDataFolder() . $source;
		$this->featureContext->userUploadsAFileTo($user, $source, $destination);
	}

	/**
	 * @When /^the public uploads file "([^"]*)" from the antivirus test data folder using the (new|old) WebDAV API$/
	 * @Given the public has uploaded file ":filename" from the antivirus test data folder
	 *
	 * @param string $source target file name
	 * @param string $publicWebDavAPIVersion
	 *
	 * @return void
	 */
	public function publicUploadsFileFromAntivirusDataFolder($source, $publicWebDavAPIVersion = "old") {
		$source = $this->getRelativePathToTestDataFolder() . $source;
		$this->publicWebDavContext->publiclyUploadingFile($source, $publicWebDavAPIVersion);
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
		$this->publicWebDavContext = $environment->getContext('PublicWebDavContext');
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
		AppConfigHelper::modifyAppConfigs(
			$this->featureContext->getBaseUrl(),
			$this->featureContext->getAdminUsername(),
			$this->featureContext->getAdminPassword(),
			[[
				'appid' => 'files_antivirus',
				'configkey' => 'av_max_file_size',
				'value' => '-1'
			]],
			2
		);
	}
}
