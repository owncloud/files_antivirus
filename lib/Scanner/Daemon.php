<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2015-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCP\ILogger;

/**
 * Class Daemon
 *
 * @package OCA\Files_Antivirus\Scanner
 */
class Daemon extends External {

	/**
	 * @var string
	 */
	private $avHost;

	/**
	 * @var int
	 */
	private $avPort;

	/**
	 * Daemon constructor.
	 *
	 * @param AppConfig $config
	 * @param ILogger $logger
	 *
	 * @throws InitException
	 */
	public function __construct(AppConfig $config, ILogger $logger) {
		parent::__construct($config, $logger);

		$this->avHost = $this->appConfig->getAvHost();
		$this->avPort = $this->appConfig->getAvPort();
		$checks = [
			'hostname' => $this->avHost,
			'port' => $this->avPort
		];
		$errors = [];
		foreach ($checks as $key => $check) {
			if ($check === '') {
				$errors[] = \sprintf(
					'Daemon mode requires a %s but it is empty.',
					$key
				);
			}
		}

		if (\count($errors) > 0) {
			throw new InitException(
				'The app is not configured properly. ' . \implode(' ', $errors)
			);
		}
	}

	/**
	 * @throws InitException
	 */
	public function initScanner() {
		parent::initScanner();
		$this->writeHandle = @\fsockopen($this->avHost, $this->avPort);
		if (!$this->getWriteHandle()) {
			throw new InitException(
				\sprintf(
					'Could not connect to host "%s" on port %d',
					$this->avHost,
					$this->avPort
				)
			);
		}
		// request scan from the daemon
		@\fwrite($this->getWriteHandle(), "nINSTREAM\n");
	}
}
