<?php

namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\IScannable;
use OCA\Files_Antivirus\Status;
use OCP\IL10N;
use OCP\ILogger;

class FortinetScanner {
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
			'res-hdr' => "POST / HTTP/1.0\r\nHost: 127.0.0.1\r\nX-Client-IP: ".$localIP."\r\nContent-Disposition: inline ; filename=test2.exe\r\nContent-Length: ".\strlen($this->data)."\r\n\r\n",
			'res-body' => $this->data
		], [
			'Allow' => 204
		]);

		$code = $response['protocol']['code'] ?? 500;
		if ($code === 200 || $code === 204) {
			$virus = $response['headers'][$this->virusHeader] ?? false;
			if ($virus) {
				return Status::create(Status::SCANRESULT_INFECTED, $virus);
			}
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
