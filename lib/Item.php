<?php
/**
 * ownCloud - files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2015-2019
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus;

use OCP\IL10N;
use OCA\Files_Antivirus\Status;
use OCA\Files_Antivirus\Activity;

class Item implements IScannable {
	/**
	 * @var IL10N
	 */
	private $l10n;
	
	/**
	 * File view
	 *
	 * @var \OC\Files\View
	 */
	protected $view;
	
	/**
	 * Path relative to the view
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Scanned fileid (optional)
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Scanned file etag (optional)
	 *
	 * @var string
	 */
	protected $etag;
	
	/**
	 * file handle, user to read from the file
	 *
	 * @var resource
	 */
	protected $fileHandle;
	
	/**
	 * Portion size
	 *
	 * @var int
	 */
	protected $chunkSize;
	
	/**
	 * Is filesize match the size conditions
	 *
	 * @var bool
	 */
	protected $isValidSize;
	
	public function __construct(IL10N $l10n, $view, $path, $id = null, $etag = null) {
		$this->l10n = $l10n;
		
		if (!\is_object($view)) {
			$this->logError('Can\'t init filesystem view.', $id, $path);
			throw new \RuntimeException();
		}
		
		if (!$view->file_exists($path)) {
			$this->logError('File does not exist.', $id, $path);
			throw new \RuntimeException();
		}
		
		if ($id === null) {
			$this->id = $view->getFileInfo($path)->getId();
		} else {
			$this->id = $id;
		}
		
		$this->view = $view;
		$this->path = $path;
		$this->etag = $etag;
		
		$this->isValidSize = $view->filesize($path) > 0;
		
		$application = new AppInfo\Application();
		$config = $application->getContainer()->query('AppConfig');
		$this->chunkSize = $config->getAvChunkSize();
	}
	
	/**
	 * Is this file good for scanning?
	 *
	 * @return boolean
	 */
	public function isValid() {
		$isValid = !$this->view->is_dir($this->path) && $this->isValidSize;
		return $isValid;
	}
	
	/**
	 * Reads a file portion by portion until the very end
	 *
	 * @return string|boolean
	 */
	public function fread() {
		if (!$this->isValid()) {
			return;
		}
		if ($this->fileHandle === null) {
			$this->getFileHandle();
		}
		
		if ($this->fileHandle !== null && !$this->feof()) {
			$chunk = \fread($this->fileHandle, $this->chunkSize);
			return $chunk;
		}
		return false;
	}
	
	/**
	 * Action to take if this item is infected
	 *
	 * @param Status $status
	 * @param boolean $isBackground
	 */
	public function processInfected(Status $status, $isBackground) {
		$application = new AppInfo\Application();
		$appConfig = $application->getContainer()->query('AppConfig');
		$infectedAction = $appConfig->getAvInfectedAction();
		
		$shouldDelete = !$isBackground || ($isBackground && $infectedAction === 'delete');
		
		$message = $shouldDelete ? Activity::MESSAGE_FILE_DELETED : '';
		
		\OC::$server->getActivityManager()->publishActivity(
			'files_antivirus',
			Activity::SUBJECT_VIRUS_DETECTED,
			[$this->path, $status->getDetails()],
			$message,
			[],
			$this->path,
			'',
			$this->view->getOwner($this->path),
			Activity::TYPE_VIRUS_DETECTED,
			Activity::PRIORITY_HIGH
		);
		if ($isBackground) {
			if ($shouldDelete) {
				$this->logError('Infected file deleted. ' . $status->getDetails());
				$this->view->unlink($this->path);
			} else {
				$this->logError('File is infected. ' . $status->getDetails());
			}
		} else {
			$this->logError('Virus(es) found: ' . $status->getDetails());
			//remove file
			$this->view->unlink($this->path);
			Notification::sendMail($this->path);
			$message = $this->l10n->t(
				"Virus detected! Can't upload the file %s",
				[\basename($this->path)]
			);
			\OCP\JSON::error(["data" => ["message" => $message]]);
			exit();
		}
	}

	/**
	 * Action to take if this item status is unclear
	 *
	 * @param Status $status
	 * @param boolean $isBackground
	 */
	public function processUnchecked(Status $status, $isBackground) {
		//TODO: Show warning to the user: The file can not be checked
		$this->logError('Not Checked. ' . $status->getDetails());
	}
	
	/**
	 * Action to take if this item status is not infected
	 *
	 * @param Status $status
	 * @param boolean $isBackground
	 */
	public function processClean(Status $status, $isBackground) {
		if (!$isBackground || $this->id === null) {
			return;
		}
		try {
			$dbConnection = \OC::$server->getDatabaseConnection();
			$dbConnection->upsert(
				'files_antivirus',
				[
					'fileid' => $this->id,
					'check_time' => \time(),
					'etag' => $this->etag
				],
				['fileid']
			);
		} catch (\Exception $e) {
			\OCP\Util::writeLog(
				'files_antivirus',
				__METHOD__ . ', exception: ' . $e->getMessage(),
				\OCP\Util::ERROR
			);
		}
	}

	/**
	 * Check if the end of file is reached
	 *
	 * @return boolean
	 */
	private function feof() {
		$isDone = \feof($this->fileHandle);
		if ($isDone) {
			$this->logDebug('Scan is done');
			\fclose($this->fileHandle);
			$this->fileHandle = null;
		}
		return $isDone;
	}
	
	/**
	 * Opens a file for reading
	 *
	 * @throws \RuntimeException
	 */
	private function getFileHandle() {
		$fileHandle = $this->view->fopen($this->path, "r");
		if ($fileHandle === false) {
			$this->logError('Can not open for reading.', $this->id, $this->path);
			throw new \RuntimeException();
		} else {
			$this->logDebug('Scan started');
			$this->fileHandle = $fileHandle;
		}
	}
	
	/**
	 * @param string $message
	 */
	public function logDebug($message) {
		$extra = ' File: ' . $this->id
				. ' Account: ' . $this->view->getOwner($this->path)
				. ' Path: ' . $this->path;
		\OCP\Util::writeLog('files_antivirus', $message . $extra, \OCP\Util::DEBUG);
	}
	
	/**
	 * @param string $message
	 * @param int $id optional
	 * @param string $path optional
	 */
	public function logError($message, $id=null, $path=null) {
		$ownerInfo = $this->view === null ? '' : ' Account: ' . $this->view->getOwner($path);
		$extra = ' File: ' . ($id === null ? $this->id : $id)
				. $ownerInfo
				. ' Path: ' . ($path === null ? $this->path : $path);
		\OCP\Util::writeLog(
			'files_antivirus',
			$message . $extra,
			\OCP\Util::ERROR
		);
	}
}
