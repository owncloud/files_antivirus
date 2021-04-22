<?php
/**
 * ownCloud - files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2021
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\AppInfo;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\AvirWrapper;
use OCA\Files_Antivirus\Cron\Task;
use OCA\Files_Antivirus\Db\RuleMapper;
use OCA\Files_Antivirus\Db\FileCollection;
use OCA\Files_Antivirus\RequestHelper;
use OCA\Files_Antivirus\ScannerFactory;
use OCP\AppFramework\App;
use OCP\IL10N;

class Application extends App {
	public function __construct(array $urlParams = []) {
		parent::__construct('files_antivirus', $urlParams);

		$container = $this->getContainer();
		$container->registerService(
			'AppConfig',
			function ($c) {
				return new AppConfig(
					$c->query('CoreConfig'),
					$c->query('ServerContainer')->getLicenseManager(),
					$c->query('ServerContainer')->getLogger()
				);
			}
		);

		$container->registerService(
			'OCA\Files_Antivirus\Cron\Task',
			function ($c) {
				return new Task(
					$c->query('ServerContainer')->getUserSession(),
					$c->query('ServerContainer')->getLogger(),
					$c->query('ServerContainer')->getRootFolder(),
					$c->query('L10N'),
					$c->query('ScannerFactory'),
					$c->query('AppConfig'),
					$c->query('FileCollection')
				);
			}
		);

		$container->registerService(
			'ScannerFactory',
			function ($c) {
				return new ScannerFactory(
					$c->query('AppConfig'),
					$c->query('Logger'),
					$c->query(IL10N::class)
				);
			}
		);

		$container->registerService(
			'FileCollection',
			function ($c) {
				return new FileCollection(
					$c->query('ServerContainer')->getDatabaseConnection(),
					$c->query('ServerContainer')->getMimeTypeLoader()
				);
			}
		);

		$container->registerService(
			'RuleMapper',
			function ($c) {
				return new RuleMapper(
					$c->query('ServerContainer')->getDb()
				);
			}
		);

		$container->registerService(
			'RequestHelper',
			function ($c) {
				return new RequestHelper(
					$c->query('ServerContainer')->getRequest()
				);
			}
		);

		/**
		 * Core
		 */
		$container->registerService(
			'Logger',
			function ($c) {
				return $c->query('ServerContainer')->getLogger();
			}
		);
		$container->registerService(
			'CoreConfig',
			function ($c) {
				return $c->query('ServerContainer')->getConfig();
			}
		);
		$container->registerService(
			'L10N',
			function ($c) {
				return $c->query('ServerContainer')->getL10N($c->query('AppName'));
			}
		);
	}

	/**
	 * Initial app setup
	 *
	 * @return void
	 */
	public function init() {
		\OCP\Util::connectHook('OC_Filesystem', 'preSetup', $this, 'setupWrapper');
		$server = $this->getContainer()->getServer();

		$server->getActivityManager()->registerExtension(
			function () use ($server) {
				return new \OCA\Files_Antivirus\Activity(
					$server->query('L10NFactory'),
					$server->getURLGenerator()
				);
			}
		);
	}

	/**
	 * Add wrapper for local storages
	 *
	 * @return void
	 */
	public function setupWrapper() {
		\OC\Files\Filesystem::addStorageWrapper(
			'oc_avir',
			function ($mountPoint, $storage) {
				/**
				 * @var \OC\Files\Storage\Storage $storage
				 */
				if ($storage instanceof \OC\Files\Storage\Storage) {
					$appConfig = $this->getContainer()->query('AppConfig');
					$scannerFactory = $this->getContainer()->query('ScannerFactory');
					$l10n = $this->getContainer()->query('L10N');
					$logger = $this->getContainer()->query('Logger');
					$requestHelper = $this->getContainer()->query('RequestHelper');
					return new AvirWrapper(
						[
							'storage' => $storage,
							'appConfig' => $appConfig,
							'scannerFactory' => $scannerFactory,
							'l10n' => $l10n,
							'logger' => $logger,
							'requestHelper' => $requestHelper
						]
					);
				} else {
					return $storage;
				}
			},
			1
		);
	}

	/**
	 * Probing all modes in a sane order
	 */
	public function autoProbe() {
		$appConfig = $this->getContainer()->query('AppConfig');
		$scannerFactory = $this->getContainer()->query('ScannerFactory');
		$set = false;
		foreach (['daemon', 'socket'] as $mode) {
			$appConfig->setAvMode($mode);
			if ($scannerFactory->testConnection($appConfig) === true) {
				$set = true;
				break;
			}
		}

		if ($set === false) {
			$appConfig->setAvMode('executable');
		}
		$appConfig->setAppValue('autoprobe', true);
	}
}
