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

abstract class AbstractScanner implements IScanner {
	/**
	 * Scan result
	 *
	 * @var Status|null
	 */
	protected $status = null;

	/**
	 * If scanning was done part by part
	 * the first detected infected part is stored here
	 *
	 * @var Status|null
	 */
	protected $infectedStatus = null;
	/**
	 * @var int
	 */
	protected $byteCount;

	/**
	 * @var resource
	 */
	protected $writeHandle;
	/**
	 * @var AppConfig
	 */
	protected $appConfig;
	/**
	 * @var ILogger
	 */
	protected $logger;
	/**
	 * @var string|null
	 */
	protected $lastChunk = null;
	/**
	 * @var bool
	 */
	protected $isLogUsed = false;
	/**
	 * @var bool
	 */
	protected $isAborted = false;
	/**
	 * @var string
	 */
	private $filename;

	/**
	 * Close used resources
	 */
	abstract public function shutdownScanner(): void;

	/**
	 * AbstractScanner constructor.
	 *
	 * @param AppConfig $config
	 * @param ILogger $logger
	 * @param IL10N $l10N
	 */
	public function __construct(AppConfig $config, ILogger $logger, IL10N $l10N) {
		$this->appConfig = $config;
		$this->logger = $logger;
	}

	/**
	 * @return Status
	 */
	public function getStatus(): Status {
		if ($this->infectedStatus instanceof Status) {
			return $this->infectedStatus;
		}
		if ($this->status instanceof Status) {
			return $this->status;
		}
		return new Status();
	}

	public function scan(IScannable $item): Status {
		$this->initScanner($item->getFilename());

		$sizeLimit = $this->appConfig->getAvMaxFileSize();
		$hasNoSizeLimit = $sizeLimit === -1;
		$scannedBytes = 0;
		while (($chunk = $item->fread()) !== false && ($hasNoSizeLimit || $scannedBytes <= $sizeLimit)) {
			if ($hasNoSizeLimit === false && $scannedBytes + \strlen($chunk) > $sizeLimit) {
				$chunk = \substr($chunk, 0, $sizeLimit - $scannedBytes);
			}
			$this->writeChunk($chunk);
			$scannedBytes += \strlen($chunk);
		}

		$this->shutdownScanner();
		return $this->getStatus();
	}

	/**
	 * Async scan - new portion of data is available
	 *
	 * @param string $data
	 */
	public function onAsyncData($data): void {
		$this->writeChunk($data);
	}

	public function completeAsyncScan(): Status {
		$this->shutdownScanner();
		return $this->getStatus();
	}

	/**
	 * Get write handle here.
	 * Do NOT open connection in constructor because this method
	 * is used for reconnection
	 */
	public function initScanner(string $fileName): void {
		$this->filename = $fileName;
		$this->byteCount = 0;
		if ($this->status && $this->status->getNumericStatus() === Status::SCANRESULT_INFECTED
		) {
			$this->infectedStatus = clone $this->status;
		}
		$this->status = new Status();
	}

	/**
	 * @param string $chunk
	 */
	protected function writeChunk(string $chunk): void {
		$this->fwrite(
			$this->prepareChunk($chunk)
		);
	}

	final protected function fwrite(string $data): void {
		if ($this->isAborted) {
			return;
		}

		$dataLength = \strlen($data);
		$streamSizeLimit = $this->appConfig->getAvStreamMaxLength();
		if ($this->byteCount + $dataLength > $streamSizeLimit) {
			$this->logger->debug(
				'reinit scanner',
				['app' => 'files_antivirus']
			);
			$this->shutdownScanner();
			$isReopenSuccessful = $this->retry();
		} else {
			$isReopenSuccessful = true;
		}

		if (!$isReopenSuccessful || !$this->writeRaw($data)) {
			if (!$this->isLogUsed) {
				$this->isLogUsed = true;
				$this->logger->warning(
					'Failed to write a chunk. Check if Stream Length matches StreamMaxLength in ClamAV daemon settings',
					['app' => 'files_antivirus']
				);
			}
			// retry on error
			$isRetrySuccessful = $this->retry() && $this->writeRaw($data);
			$this->isAborted = !$isRetrySuccessful;
		}
	}

	protected function retry(): bool {
		$this->initScanner($this->filename);
		if ($this->lastChunk !== null) {
			return $this->writeRaw($this->lastChunk);
		}
		return true;
	}

	protected function writeRaw(string $data): bool {
		$dataLength = \strlen($data);
		$bytesWritten = @\fwrite($this->getWriteHandle(), $data);
		if ($bytesWritten === $dataLength) {
			$this->byteCount += $bytesWritten;
			$this->lastChunk = $data;
			return true;
		}
		return false;
	}

	/**
	 * Get a resource to write data into
	 *
	 * @return resource
	 */
	protected function getWriteHandle() {
		return $this->writeHandle;
	}

	/**
	 * Prepare chunk (if needed)
	 */
	protected function prepareChunk(string $data): string {
		return $data;
	}
}
