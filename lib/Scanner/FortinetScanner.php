<?php

namespace OCA\Files_Antivirus\Scanner;

class FortinetScanner extends ICAPScanner {
	protected function buildBodyHeaders(): array {
		$localIP = getHostByName(getHostName());
		$contentLength = $this->getContentLength();
		$fileName = $this->getFileName();
		return [
			"POST / HTTP/1.0",
			"Host: 127.0.0.1",
			"X-Client-IP: $localIP",
			"Content-Disposition: inline ; filename=$fileName",
			"Content-Length: $contentLength",
		];
	}

	protected function usesReqMod(): bool {
		return false;
	}
}
