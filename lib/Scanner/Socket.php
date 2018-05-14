<?php
/**
 * Copyright (c) 2017 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCP\ILogger;

/**
 * Class Socket
 *
 * @package OCA\Files_Antivirus\Scanner
 */
class Socket extends External {

	/**
	 * @var string
	 */
	private $socket;

	/**
	 * Socket constructor.
	 *
	 * @param AppConfig $config
	 * @param ILogger $logger
	 *
	 * @throws InitException
	 */
	public function __construct(AppConfig $config, ILogger $logger) {
		parent::__construct($config, $logger);
		$this->socket = $this->appConfig->getAvSocket();
		if ($this->socket === '') {
			throw new InitException(
				'Socket mode requires a path to the unix socket but it is empty.'
			);
		}
	}

	/**
	 * @throws InitException
	 */
	public function initScanner() {
		parent::initScanner();
		$this->writeHandle = @\stream_socket_client(
			'unix://' . $this->socket, $errorCode, $errorMessage, 5
		);
		if (!$this->getWriteHandle()) {
			throw new InitException(
				\sprintf(
					'Could not connect to socket "%s": %s (code %d)',
					$this->socket,
					$errorMessage,
					$errorCode
				)
			);
		}

		if (@\fwrite($this->getWriteHandle(), "nINSTREAM\n") === false) {
			throw new InitException(
				\sprintf(
					'Writing to socket "%s" failed',
					$this->socket
				)
			);
		}
	}
}
