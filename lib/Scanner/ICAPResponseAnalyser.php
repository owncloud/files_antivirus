<?php

namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\Status;

class ICAPResponseAnalyser {
	/** @var string */
	private $virusHeader;

	public function __construct(string $virusHeader) {
		$this->virusHeader = $virusHeader;
	}

	public function analyseResponse(array $response): ?array {
		$code = $response['protocol']['code'] ?? 500;
		if ($code === 100 || $code === 200 || $code === 204) {
			// c-icap/clamav reports this header - McAfee 11 reports the virus name in `res-hdr`
			$virus = $response['headers'][$this->virusHeader] ?? $response['body']['res-hdr'][$this->virusHeader] ?? false;
			if ($virus) {
				return [Status::SCANRESULT_INFECTED, $virus];
			}

			// kaspersky(pre-2020 product editions) and McAfee handling
			$respHeader = $response['body']['res-hdr']['HTTP_STATUS'] ?? '';
			if (\strpos($respHeader, '403 Forbidden') !== false || \strpos($respHeader, '403 VirusFound') !== false) {
				return [Status::SCANRESULT_INFECTED];
			}
			return [Status::SCANRESULT_CLEAN];
		}

		return null;
	}
}
