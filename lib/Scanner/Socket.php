<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2017-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCP\IL10N;
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
	 * @throws InitException
	 */
	public function __construct(AppConfig $config, ILogger $logger, IL10N $l10n) {
		parent::__construct($config, $logger, $l10n);
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
	public function initScanner(string $fileName): void {
		parent::initScanner($fileName);
		$this->writeHandle = @\stream_socket_client(
			'unix://' . $this->socket,
			$errorCode,
			$errorMessage,
			5
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

		// Check we're connecting to a ClamAV daemon
		$pingResult = $this->sendCommand("PING", 6); // PONG plus newline chars is expected
		if (\rtrim($pingResult, "\r\n") !== "PONG") {
			throw new InitException("Unexpected response to ping: $pingResult");
		}

		$versionResult = $this->sendCommand("VERSION", 500);
		// The response can vary, and it's difficult to predict the size of the response,
		// but 500 bytes should be more than enough to fit the whole response.
		// Just check that it starts with "ClamAV"
		if (\strpos($versionResult, 'ClamAV') !== 0) {
			throw new InitException("Unexpected response to version: $versionResult");
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

	/**
	 * NOTE: ClamAV closes the connection after each command, so we need to
	 * open a new connection each time.
	 */
	private function sendCommand(string $clamavCmd, int $maxResponseSize): string {
		$handle = @\stream_socket_client("unix://{$this->socket}", $errorCode, $errorMsg, 5);
		if ($handle === false) {
			throw new InitException("Failed to connect to socket: {$errorMsg} ({$errorCode})");
		}

		$bytesWritten = @\fwrite($handle, "{$clamavCmd}\n");
		if ($bytesWritten !== \strlen($clamavCmd) + 1) {
			throw new InitException("Failed to write {$clamavCmd} command in the handle. Bytes written: {$bytesWritten}");
		}

		$result = @\fgets($handle, $maxResponseSize);
		@fclose($handle);

		return $result;
	}
}
