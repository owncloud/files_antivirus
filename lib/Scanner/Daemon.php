<?php
/**
 * Copyright (c) 2014 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OCA\Files_Antivirus\Scanner;

class Daemon extends AbstractScanner {
	
	public function initScanner(){
		parent::initScanner();

		$avHost = $this->appConfig->getAvHost();
		$avPort = $this->appConfig->getAvPort();
		$checks = [
			'hostname' => $avHost,
			'port' => $avPort
		];
		$errors = [];
		foreach ($checks as $key => $check) {
			if ($check === '') {
				$errors[] = sprintf(
					'Daemon mode requires a %s but it is empty.',
					$key
				);
			}
		}

		if (count($errors)>0) {
			throw new InitException(
				'The app is not configured properly. ' . implode(' ', $errors)
			);
		}

		$this->writeHandle = @fsockopen($avHost, $avPort);
		if (!$this->getWriteHandle()) {
			throw new InitException(
				sprintf(
					'Could not connect to host "%s" on port %d', $avHost, $avPort
				)
			);
		}

		// request scan from the daemon
		@fwrite($this->getWriteHandle(), "nINSTREAM\n");
	}
	
	protected function shutdownScanner(){
		@fwrite($this->getWriteHandle(), pack('N', 0));
		$response = fgets($this->getWriteHandle());
		$this->logger->debug(
			'Response :: ' . $response,
			['app' => 'files_antivirus']
		);
		@fclose($this->getWriteHandle());
		
		$this->status->parseResponse($response);
	}
	
	protected function prepareChunk($data){
		$chunkLength = pack('N', strlen($data));
		return $chunkLength . $data;
	}
}
