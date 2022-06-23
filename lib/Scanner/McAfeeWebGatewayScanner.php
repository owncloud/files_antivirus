<?php

namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\IScannable;
use OCA\Files_Antivirus\Status;
use OCP\IL10N;
use OCP\ILogger;

class McAfeeWebGatewayScanner {
	/** @var IL10N */
	private $l10n;

	private $data = '';
	private $host;
	private $port;
	private $reqService;
	private $virusHeader;
	private $sizeLimit;

	public function __construct(AppConfig $config, ILogger $logger, IL10N $l10n) {
		$this->host = $config->getAvHost();
		$this->port = $config->getAvPort();
		$this->reqService = $config->getAvRequestService();
		$this->virusHeader = $config->getAvResponseHeader();
		$this->sizeLimit = $config->getAvMaxFileSize();
		$this->l10n = $l10n;
        $this->logger = $logger;
	}

	public function initScanner() {
	}

	public function onAsyncData($data) {
		$hasNoSizeLimit = $this->sizeLimit === -1;
		$scannedBytes = \strlen($this->data);
		if ($hasNoSizeLimit || $scannedBytes <= $this->sizeLimit) {
			if ($hasNoSizeLimit === false && $scannedBytes + \strlen($data) > $this->sizeLimit) {
				$data = \substr($data, 0, $this->sizeLimit - $scannedBytes);
			}
			$this->data .= $data;
		}
	}

	public function completeAsyncScan() {
		if ($this->data === '') {
			return Status::create(Status::SCANRESULT_CLEAN);
		}
		$localIP = getHostByName(getHostName());

		$c = new ICAPClient($this->host, $this->port);
		$response = $c->respmod($this->reqService, [
			'req-hdr' => '', 'res-hdr' => "GET http://localhost/ HTTP/1.1\r\nDNT: 1\r\nX-Client-IP: ".$localIP."\r\nContent-Length: ".\strlen($this->data)."\r\n\r\n",
			'res-body' => $this->data
		], [
			'Preview' => \strlen($this->data),
			'X-Client-IP' => $localIP,
			'Allow' => 204
		]);


        $code = $response['protocol']['code'] ?? 500;
		if ($code === 100 || $code === 200 || $code === 204) {

			// Check the Header Response from User-Input 
            // FIXME McAfee adds the X-Headers after Encapsulated. Parser should be improved. Never runs into $virus.
			$virus = $response['headers'][$this->virusHeader] ?? false;
            if ($virus) {
				return Status::create(Status::SCANRESULT_INFECTED, $virus);
			}

			// McAfee Webgateway if X-Headers are afterwards Encapsulated are sent
            // FIXME Parser should be improved. We catch in the second try the return states.
			$respHeader = $response['body']['res-hdr']['HTTP_STATUS'] ?? '';
			if (\strpos($respHeader, '403 Forbidden') !== false || \strpos($respHeader, '403 VirusFound') !== false || \strpos($respHeader, 'X-Virus-Name')  !== false ) {
				$message = $this->l10n->t('A malware or virus was detected, your upload was deleted. In doubt or for details please contact your system administrator');
				return Status::create(Status::SCANRESULT_INFECTED, $message);
			}			
			return Status::create(Status::SCANRESULT_CLEAN);
		} else {
			throw new \RuntimeException('AV failed!');
		}
		return Status::create(Status::SCANRESULT_CLEAN);
	}

	public function scan(IScannable $item) {
		$this->initScanner();
		while (($chunk = $item->fread()) !== false) {
			$this->onAsyncData($chunk);
		}
		$status = $this->completeAsyncScan();

		$this->shutdownScanner();
		return $status;
	}

	protected function shutdownScanner() {
	}
}
