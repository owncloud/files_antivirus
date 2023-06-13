<?php

namespace OCA\Files_Antivirus\Scanner;

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
