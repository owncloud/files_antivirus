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
use OCP\IL10N;
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
	 * @throws InitException
	 */
	public function __construct(AppConfig $config, ILogger $logger, IL10N $l10n) {
		parent::__construct($config, $logger, $l10n);

		$this->avHost = $this->appConfig->getAvHost();
		$avPort = $this->appConfig->getAvPort();
		$checks = [
			'hostname' => $this->avHost,
			'port' => $avPort
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

		$this->avPort = $avPort;
	}

	/**
	 * @throws InitException
	 */
	public function initScanner(string $fileName): void {
		parent::initScanner($fileName);
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
