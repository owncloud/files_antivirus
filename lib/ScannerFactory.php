<?php
/**
 * ownCloud - files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2014-2021
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus;

use OCA\Files_Antivirus\Scanner\ICAPScanner;
use OCA\Files_Antivirus\Scanner\InitException;
use OCP\IL10N;
use \OCP\ILogger;
use OCA\Files_Antivirus\Scanner\Local;
use OCA\Files_Antivirus\Scanner\Socket;
use OCA\Files_Antivirus\Scanner\Daemon;

class ScannerFactory {
	// We split it in two parts in order to prevent reports from av scanners
	public const EICAR_PART_1 = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$';
	public const EICAR_PART_2 = 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

	/**
	 * @var AppConfig
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
	/**
	 * @var IL10N
	 */
	private $l10N;

	public function __construct(AppConfig $appConfig, ILogger $logger, IL10N $l10N) {
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
		$this->l10N = $l10N;
	}

	/**
	 * @throws InitException
	 */
	protected function getScannerClass() {
		switch ($this->appConfig->getAvMode()) {
			case 'daemon':
				$this->scannerClass = Daemon::class;
				break;
			case 'socket':
				$this->scannerClass = Socket::class;
				break;
			case 'executable':
				$this->scannerClass = Local::class;
				break;
			case 'icap':
				$this->scannerClass = ICAPScanner::class;
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
		return new $this->scannerClass($this->appConfig, $this->logger, $this->l10N);
	}

	/**
	 * @param AppConfig $appConfig
	 *
	 * @return bool
	 */
	public function testConnection(AppConfig $appConfig) {
		$this->appConfig = $appConfig;
		try {
			$this->getScannerClass();
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
