<?php
/**
 * ownCloud - Files_antivirus
 *
 * @author Manuel Deglado <manuel.delgado@ucr.ac.cr>
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright 2012 Manuel Deglado manuel.delgado@ucr.ac.cr
 * @copyright 2014-2018 Viktar Dubiniuk
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
use OCP\ILogger;

abstract class AbstractScanner {
	
	/**
	 * Scan result
	 *
	 * @var Status
	 */
	protected $status;

	/**
	 * If scanning was done part by part
	 * the first detected infected part is stored here
	 *
	 * @var Status
	 */
	protected $infectedStatus;

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
	 * @var  ILogger
	 */
	protected $logger;

	/**
	 * @var string
	 */
	protected $lastChunk;

	/**
	 * @var bool
	 */
	protected $isLogUsed = false;

	/**
	 * @var bool
	 */
	protected $isAborted = false;

	/**
	 * Close used resources
	 */
	abstract protected function shutdownScanner();

	/**
	 * AbstractScanner constructor.
	 *
	 * @param AppConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(AppConfig $config, ILogger $logger) {
		$this->appConfig = $config;
		$this->logger = $logger;
	}

	/**
	 * @return Status
	 */
	public function getStatus() {
		if ($this->infectedStatus instanceof Status) {
			return $this->infectedStatus;
		}
		if ($this->status instanceof Status) {
			return $this->status;
		}
		return new Status();
	}

	/**
	 * Synchronous scan
	 *
	 * @param IScannable $item
	 *
	 * @return Status
	 */
	public function scan(IScannable $item) {
		$this->initScanner();

		while (($chunk = $item->fread()) !== false) {
			$this->writeChunk($chunk);
		}
		
		$this->shutdownScanner();
		return $this->getStatus();
	}
	
	/**
	 * Async scan - new portion of data is available
	 *
	 * @param string $data
	 */
	public function onAsyncData($data) {
		$this->writeChunk($data);
	}
	
	/**
	 * Async scan - resource is closed
	 *
	 * @return Status
	 */
	public function completeAsyncScan() {
		$this->shutdownScanner();
		return $this->getStatus();
	}
	
	/**
	 * Get write handle here.
	 * Do NOT open connection in constructor because this method
	 * is used for reconnection
	 */
	public function initScanner() {
		$this->byteCount = 0;
		if ($this->status instanceof Status
			&& $this->status->getNumericStatus() === Status::SCANRESULT_INFECTED
		) {
			$this->infectedStatus = clone $this->status;
		}
		$this->status = new Status();
	}

	/**
	 * @param string $chunk
	 */
	protected function writeChunk($chunk) {
		$this->fwrite(
			$this->prepareChunk($chunk)
		);
	}

	/**
	 * @param string $data
	 */
	final protected function fwrite($data) {
		if ($this->isAborted) {
			return;
		}

		$dataLength = \strlen($data);
		$streamSizeLimit = \intval($this->appConfig->getAvStreamMaxLength());
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

	/**
	 * @return bool
	 */
	protected function retry() {
		$this->initScanner();
		if ($this->lastChunk !== null) {
			return $this->writeRaw($this->lastChunk);
		}
		return true;
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	protected function writeRaw($data) {
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
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	protected function prepareChunk($data) {
		return $data;
	}
}
