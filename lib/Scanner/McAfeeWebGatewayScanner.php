<?php

namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\IScannable;
use OCA\Files_Antivirus\Status;
use OCP\IL10N;
use OCP\ILogger;

class McAfeeWebGatewayScanner extends ICAPScanner {
	protected function usesReqMod(): bool {
		return false;
	}

	protected function buildBodyHeaders(): array {
		$localIP = getHostByName(getHostName());
		$contentLength = $this->getContentLength();
		return [
			"GET http://localhost/ HTTP/1.1",
			"DNT: 1",
			"X-Client-IP: $localIP",
			"Content-Length: $contentLength",

		];
	}

	protected function getICAPHeaders(): array {
		$localIP = getHostByName(getHostName());
		return [
			'Preview' => $this->getContentLength(),
			'X-Client-IP' => $localIP,
			'Allow' => 204
		];
	}

	protected function buildRespModBody(): array {
		$data =  parent::buildRespModBody();
		$data['req-hdr'] = "";

		return $data;
	}
}
