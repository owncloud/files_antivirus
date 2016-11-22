<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus;

use OCP\IUserManager;
use OCP\IL10N;

class BackgroundScanner {

	const BATCH_SIZE = 10;

	/** @var ScannerFactory */
	private $scannerFactory;
	
	/** @var IUserManager */
	private $userManager;

	/** @var IL10N */
	private $l10n;

	/** @var  AppConfig  */
	private $appConfig;

	/**
	 * A constructor
	 *
	 * @param \OCA\Files_Antivirus\ScannerFactory $scannerFactory
	 * @param AppConfig $appConfig
	 * @param IUserManager $userManager
	 * @param IL10N $l10n
	 */
	public function __construct(ScannerFactory $scannerFactory, AppConfig $appConfig, IUserManager $userManager, IL10N $l10n){
		$this->scannerFactory = $scannerFactory;
		$this->userManager = $userManager;
		$this->l10n = $l10n;
		$this->appConfig = $appConfig;
	}
	
	/**
	 * Background scanner main job
	 * @return null
	 */
	public function run(){
		if (!$this->initFS()) {
			return;
		}
		// locate files that are not checked yet
		$dirMimeTypeId = \OC::$server->getMimeTypeLoader()->getId('httpd/unix-directory');
		try {
			$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
			$qb->select(['fc.fileid'])
				->from('filecache', 'fc')
				->leftJoin('fc', 'files_antivirus', 'fa', $qb->expr()->eq('fa.fileid', 'fc.fileid'))
				->innerJoin(
					'fc',
					'storages',
					'ss',
					$qb->expr()->andX(
						$qb->expr()->eq('fc.storage', 'ss.numeric_id'),
						$qb->expr()->orX(
							$qb->expr()->like('ss.id', $qb->expr()->literal('local::%')),
							$qb->expr()->like('ss.id', $qb->expr()->literal('home::%'))
						)
					)
				)
				->where(
					$qb->expr()->neq('fc.mimetype', $qb->expr()->literal($dirMimeTypeId))
				)
				->andWhere(
					$qb->expr()->orX(
						$qb->expr()->isNull('fa.fileid'),
						$qb->expr()->gt('fc.mtime', 'fa.check_time')
					)
				)
				->andWhere(
					$qb->expr()->like('fc.path', $qb->expr()->literal('files/%'))
				)
				->andWhere(
					$qb->expr()->neq('fc.size', $qb->expr()->literal('0'))
				)
			;
			$result = $qb->execute();
		} catch(\Exception $e) {
			\OC::$server->getLogger()->error( __METHOD__ . ', exception: ' . $e->getMessage(), ['app' => 'files_antivirus']);
			return;
		}

		$view = new \OC\Files\View('');
		$cnt = 0;
		while (($row = $result->fetch()) && $cnt < self::BATCH_SIZE) {
			try {
				$path = $view->getPath($row['fileid']);
				if (!is_null($path)) {
					$item = new Item($this->l10n, $view, $path, $row['fileid']);
					$scanner = $this->scannerFactory->getScanner();
					$status = $scanner->scan($item);
					$status->dispatch($item, true);
 					// increased only for successfully scanned files
					$cnt = $cnt + 1;
				}
			} catch (\Exception $e){
				\OC::$server->getLogger()->debug( __METHOD__ . ', exception: ' . $e->getMessage(), ['app' => 'files_antivirus']);
			}
		}
		\OC_Util::tearDownFS();
	}

	/**
	 * A hack to access files and views. Better than before.
	 *
	 * @return bool
	 */
	protected function initFS(){
		//Need any valid user to mount FS
		$results = $this->userManager->search('', 2, 0);
		$anyUser = array_pop($results);
		if (is_null($anyUser)) {
			\OC::$server->getLogger()->error("Failed to setup file system", ['app' => 'files_antivirus']);
			return false;
		}
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($anyUser->getUID());
		return true;
	}

	/**
	 * @deprecated 
	 */
	public static function check(){
		return \OCA\Files_Antivirus\Cron\Task::run();
	}
}
