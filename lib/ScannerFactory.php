<?php
/**
 * Copyright (c) 2014 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus;

use OCA\Files_Antivirus\Scanner\InitException;
use \OCP\ILogger;

class ScannerFactory{
	
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
	
	public function __construct(AppConfig $appConfig, ILogger $logger){
		$this->appConfig = $appConfig;
		$this->logger = $logger;
		try {
			$avMode = $appConfig->getAvMode();
			switch ($avMode) {
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
						sprintf(
							'Please check the settings at the admin page. Invalid mode: "%s"',
							$avMode
						)
					);
			}
		} catch (InitException $e) {
			// TODO: add a new config option to prevent upload on misconfiguration
			//  and check it here
			$this->logger->warning(
				sprintf(
					'Application is misconfigured. %s',
					$e->getMessage()
				),
				['app' => 'files_antivirus']
			);
		} catch (\Exception $e) {
			$message = 	implode(' ', [ __CLASS__, __METHOD__, $e->getMessage()]);
			$this->logger->warning($message, ['app' => 'files_antivirus']);
		}
	}
	
	/**
	 * Produce a scanner instance 
	 * @return \OCA\Files_Antivirus\Scanner\AbstractScanner
	 */
	public function getScanner(){
		return new $this->scannerClass($this->appConfig, $this->logger);
	}
}
