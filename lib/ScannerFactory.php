<?php
/**
 * ownCloud - files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2014-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus;

use OCA\Files_Antivirus\Scanner\InitException;
use \OCP\ILogger;

class ScannerFactory {
	// We split it in two parts in order to prevent reports from av scanners
	const EICAR_PART_1 = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$';
	const EICAR_PART_2 = 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
	
	/**
	 * @var \OCA\Files_Antivirus\AppConfig
	 */
	protected $appConfig;
	
	/**
	 * @var ILogger;
	 */
	protected $logger;
	
	/**
	 * @var string
	 */
	protected $scannerClass;
	
	public function __construct(AppConfig $appConfig, ILogger $logger) {
		$this->appConfig = $appConfig;
		$this->logger = $logger;
		try {
			$this->getScannerClass();
		} catch (InitException $e) {
			// rethrow misconfiguration exception
			throw $e;
		} catch (\Exception $e) {
			$message = 	\implode(' ', [ __CLASS__, __METHOD__, $e->getMessage()]);
			$this->logger->warning($message, ['app' => 'files_antivirus']);
		}
	}

	/**
	 * @throws InitException
	 */
	protected function getScannerClass() {
		switch ($this->appConfig->getAvMode()) {
			case 'daemon':
				$this->scannerClass = 'OCA\Files_Antivirus\Scanner\Daemon';
				break;
			case 'socket':
				$this->scannerClass = 'OCA\Files_Antivirus\Scanner\Socket';
				break;
			case 'executable':
				$this->scannerClass = 'OCA\Files_Antivirus\Scanner\Local';
				break;
			default:
				throw new InitException(
					\sprintf(
						'Please check the settings at the admin page. Invalid mode: "%s"',
						$this->appConfig->getAvMode()
					)
				);
		}
	}
	
	/**
	 * Produce a scanner instance
	 *
	 * @return \OCA\Files_Antivirus\Scanner\AbstractScanner
	 */
	public function getScanner() {
		return new $this->scannerClass($this->appConfig, $this->logger);
	}

	/**
	 * @param AppConfig $appConfig
	 *
	 * @return bool
	 */
	public function testConnection(AppConfig $appConfig) {
		$this->appConfig = $appConfig;
		$this->getScannerClass();
		try {
			$scanner = $this->getScanner();
			$item = new Content(self::EICAR_PART_1 . self::EICAR_PART_2, 4096);
			$status = $scanner->scan($item);
			return $status->getNumericStatus() === Status::SCANRESULT_INFECTED;
		} catch (\Exception $e) {
			$this->logger->warning($e->getMessage(), ['app' => 'files_antivirus']);
			return false;
		}
	}
}
