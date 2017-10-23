<?php
/**
 * Copyright (c) 2017 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OCA\Files_Antivirus\Scanner;

class Socket extends Daemon {

	public function initScanner(){
		parent::initScanner();

		$this->writeHandle = stream_socket_client(
			'unix://' . $this->appConfig->getAvSocket(), $errorCode, $errorMessage, 5
		);
		if (!$this->getWriteHandle()) {
			throw new InitException(
				sprintf(
					'Could not connect to socket "%s": %s (code %d)',
					$this->appConfig->getAvSocket(),
					$errorMessage,
					$errorCode
				)
			);
		}

		// request scan from the daemon
		@fwrite($this->getWriteHandle(), "nINSTREAM\n");
	}
}
