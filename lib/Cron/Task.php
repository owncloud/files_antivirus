<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2015-2019
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Cron;

use OC\BackgroundJob\TimedJob;
use OC\Files\Filesystem;
use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\Db\FileCollection;
use OCA\Files_Antivirus\Item;
use OCA\Files_Antivirus\ScannerFactory;
use OCP\IL10N;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;

class Task extends TimedJob {
	const BATCH_SIZE = 10;

	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * @var \OCP\Files\Folder[]
	 */
	protected $userFolders;

	/**
	 * @var ScannerFactory
	 */
	private $scannerFactory;

	/**
	 * @var IL10N
	 */
	private $l10n;

	/**
	 * @var  AppConfig
	 */
	private $appConfig;

	/**
	 * @var string
	 */
	protected $currentFilesystemUser;

	/**
	 * @var \OCP\IUserSession
	 */
	protected $userSession;

	/**
	 * @var FileCollection
	 */
	protected $fileCollection;

	/**
	 * A constructor
	 *
	 * @param \OCA\Files_Antivirus\ScannerFactory $scannerFactory
	 * @param IL10N $l10n
	 * @param AppConfig $appConfig
	 * @param IRootFolder $rootFolder
	 * @param IUserSession $userSession
	 * @param FileCollection $fileCollection
	 */
	public function __construct(ScannerFactory $scannerFactory,
								IL10N $l10n,
								AppConfig $appConfig,
								IRootFolder $rootFolder,
								IUserSession $userSession,
								FileCollection $fileCollection
	) {
		$this->rootFolder = $rootFolder;
		$this->scannerFactory = $scannerFactory;
		$this->l10n = $l10n;
		$this->appConfig = $appConfig;
		$this->userSession = $userSession;
		$this->fileCollection = $fileCollection;

		// Run once per 15 minutes
		$this->setInterval(60 * 15);
	}

	/**
	 * @param string $argument
	 *
	 * @return void
	 */
	protected function run($argument) {
		if (!\OCP\App::isEnabled('files_antivirus')) {
			return;
		}

		if ($this->appConfig->getAvScanBackground() !== 'true') {
			return;
		}

		// locate files that are not checked yet
		try {
			$result = $this->getFilesForScan();
		} catch (\Exception $e) {
			\OC::$server->getLogger()->error(
				__METHOD__ . ', exception: ' . $e->getMessage(),
				['app' => 'files_antivirus']
			);
			return;
		}

		$cnt = 0;
		while (($row = $result->fetch()) && $cnt < self::BATCH_SIZE) {
			try {
				$fileId = $row['fileid'];
				$userId = $row['user_id'];
				/** @var IUser $owner */
				$owner = \OC::$server->getUserManager()->get($userId);
				if (!$owner instanceof IUser) {
					continue;
				}
				$this->scanOneFile($owner, $fileId);
				// increased only for successfully scanned files
				$cnt = $cnt + 1;
			} catch (\Exception $e) {
				\OC::$server->getLogger()->error(
					__METHOD__ . ', exception: ' . $e->getMessage(),
					['app' => 'files_antivirus']
				);
			}
		}
		$this->tearDownFilesystem();
	}

	/**
	 * @return \Doctrine\DBAL\Driver\Statement|int
	 */
	protected function getFilesForScan() {
		$fileSizeLimit = \intval($this->appConfig->getAvMaxFileSize());
		return $this->fileCollection->getCollection($fileSizeLimit);
	}

	/**
	 * @param IUser $owner
	 * @param int $fileId
	 */
	protected function scanOneFile($owner, $fileId) {
		$this->initFilesystemForUser($owner);
		$view = Filesystem::getView();
		$path = $view->getPath($fileId);
		\OC::$server->getLogger()->debug(
			"About to scan file of user {$owner} with id {$fileId} and path {$path}",
			['app' => 'files_antivirus']
		);
		if ($path !== null) {
			$item = new Item($this->l10n, $view, $path, $fileId);
			$scanner = $this->scannerFactory->getScanner();
			$status = $scanner->scan($item);
			$status->dispatch($item, true);
		}
	}

	/**
	 * @param \OCP\IUser $user
	 *
	 * @return \OCP\Files\Folder
	 */
	protected function getUserFolder(IUser $user) {
		if (!isset($this->userFolders[$user->getUID()])) {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$this->userFolders[$user->getUID()] = $userFolder;
		}
		return $this->userFolders[$user->getUID()];
	}

	/**
	 * @param IUser $user
	 */
	protected function initFilesystemForUser(IUser $user) {
		if ($this->currentFilesystemUser !== $user->getUID()) {
			if ($this->currentFilesystemUser !== '') {
				$this->tearDownFilesystem();
			}
			Filesystem::init($user->getUID(), '/' . $user->getUID() . '/files');
			$this->userSession->setUser($user);
			$this->currentFilesystemUser = $user->getUID();
			Filesystem::initMountPoints($user->getUID());
		}
	}

	/**
	 * @return void
	 */
	protected function tearDownFilesystem() {
		$this->userSession->setUser(null);
		\OC_Util::tearDownFS();
	}
}
