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

use OC\Files\View;
use OCP\AppFramework\QueryException;
use OCP\Files\NotFoundException;
use OCP\IL10N;

class Item implements IScannable {
	/**
	 * @var IL10N
	 */
	private $l10n;
	/**
	 * @var View
	 */
	protected $view;
	/**
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
	 * Does filesize match the size conditions
	 *
	 * @var bool
	 */
	protected $isValidSize;

	/**
	 * @throws NotFoundException
	 * @throws QueryException
	 */
	public function __construct(IL10N $l10n, View $view, string $path, int $id = null, string $etag = null) {
		$this->l10n = $l10n;
		$this->view = $view;
		$this->path = $path;
		$this->etag = $etag;

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
		
		$this->isValidSize = $view->filesize($path) > 0;
		
		$application = new AppInfo\Application();
		$config = $application->getContainer()->query('AppConfig');
		$this->chunkSize = $config->getAvChunkSize();
	}
	
	/**
	 * Is this file good for scanning?
	 */
	public function isValid(): bool {
		return !$this->view->is_dir($this->path) && $this->isValidSize;
	}

	/**
	 * Reads a file portion by portion until the very end
	 *
	 * @return string|boolean
	 * @throws NotFoundException
	 */
	public function fread() {
		if (!$this->isValid()) {
			return false;
		}
		if ($this->fileHandle === null) {
			$this->getFileHandle();
		}
		
		if ($this->fileHandle !== null && !$this->feof()) {
			return \fread($this->fileHandle, $this->chunkSize);
		}
		return false;
	}

	/**
	 * Action to take if this item is infected
	 *
	 * @param Status $status
	 * @param boolean $isBackground
	 * @throws NotFoundException
	 * @throws QueryException
	 */
	public function processInfected(Status $status, bool $isBackground): void {
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
	 * @throws NotFoundException
	 */
	public function processUnchecked(Status $status): void {
		//TODO: Show warning to the user: The file can not be checked
		$this->logError('Not Checked. ' . $status->getDetails());
	}
	
	/**
	 * Action to take if this item status is not infected
	 */
	public function processClean(bool $isBackground): void {
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
	 */
	private function feof(): bool {
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
	 * @throws NotFoundException
	 */
	private function getFileHandle(): void {
		$fileHandle = $this->view->fopen($this->path, "r");
		if ($fileHandle === false) {
			$this->logError('Can not open for reading.', $this->id, $this->path);
			throw new \RuntimeException();
		}

		$this->logDebug('Scan started');
		$this->fileHandle = $fileHandle;
	}
	
	public function logDebug(string $message): void {
		$extra = ' File: ' . $this->id
				. ' Account: ' . $this->view->getOwner($this->path)
				. ' Path: ' . $this->path;
		\OCP\Util::writeLog('files_antivirus', $message . $extra, \OCP\Util::DEBUG);
	}

	/**
	 * @throws NotFoundException
	 */
	public function logError(string $message, int $id = null, string $path = null): void {
		$ownerInfo = ' ';
		if ($path && $this->view->file_exists($path)) {
			$ownerInfo = ' Account: ' . $this->view->getOwner($path);
		}
		$extra = ' File: ' . ($id ?? $this->id)
				. $ownerInfo
				. ' Path: ' . ($path ?? $this->path);
		\OCP\Util::writeLog(
			'files_antivirus',
			$message . $extra,
			\OCP\Util::ERROR
		);
	}

	public function getFilename(): string {
		return basename($this->path);
	}
}
