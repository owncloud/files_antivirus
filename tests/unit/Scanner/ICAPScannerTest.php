<?php

namespace OCA\Files_Antivirus\Tests\unit\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\Content;
use OCA\Files_Antivirus\Scanner\FortinetScanner;
use OCA\Files_Antivirus\Scanner\ICAPScanner;
use OCA\Files_Antivirus\Scanner\InitException;
use OCA\Files_Antivirus\Status;
use OCP\IL10N;
use OCP\ILogger;
use Test\TestCase;

class ICAPScannerTest extends TestCase {
	/**
	 * @var ICAPScanner
	 */
	private $scanner;
	private $config;
	private $logger;
	private $l10n;

	protected function setUp(): void {
		parent::setUp();

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->addMethods([
				'getAvHost',
				'getAvPort',
				'getAvRequestService',
				'getAvResponseHeader',
				'getAvMaxFileSize',
			])
			->getMock();

		$logger = $this->createMock(ILogger::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->will($this->returnArgument(0));

		# for local testing replace 'icap' with the ip of the clamav instance
		$config->method('getAvHost')->willReturn('icap');
		$config->method('getAvPort')->willReturn(1344);
		$config->method('getAvRequestService')->willReturn('avscan');
		$config->method('getAvResponseHeader')->willReturn('X-Infection-Found');
		$config->method('getAvMaxFileSize')->willReturn(-1);

		$this->config = $config;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->scanner = new ICAPScanner($config, $logger, $l10n);
	}

	public function testFilename(): void {
		$this->scanner->initScanner('test.txt.ocTransferId123456.part');
		self::assertEquals('test.txt', $this->scanner->getFileName());
	}

	public function testFilenameControlCharacterInPartSuffix(): void {
		$this->scanner->initScanner("test.txt.ocTransferId12\x003456.part");
		self::assertEquals('test.txt', $this->scanner->getFileName());
	}

	public function testFilenameControlCharactersDoNotReachTheEncapsulatedHeaders(): void {
		$scanner = new FortinetScanner($this->config, $this->logger, $this->l10n);
		$scanner->initScanner("\r\nContent-Length: 0\r\ntest.txt.ocTransferId123456.part");
		$scanner->onAsyncData('abc');

		self::assertSame('Content-Length: 0test.txt', $scanner->getFileName());

		$buildRespModBody = new \ReflectionMethod($scanner, 'buildRespModBody');
		$buildRespModBody->setAccessible(true);
		$lines = \explode("\r\n", $buildRespModBody->invoke($scanner)['res-hdr']);
		self::assertCount(7, $lines);
		self::assertSame('POST / HTTP/1.0', $lines[0]);
		self::assertSame('Host: 127.0.0.1', $lines[1]);
		self::assertStringStartsWith('X-Client-IP: ', $lines[2]);
		self::assertSame('Content-Disposition: inline ; filename=Content-Length: 0test.txt', $lines[3]);
		self::assertSame('Content-Length: 3', $lines[4]);
		self::assertSame(['', ''], \array_slice($lines, 5));
	}

	/**
	 * @dataProvider providesScanData
	 * @throws InitException
	 */
	public function testScannerAsync(int $expectedStatus, string $scanData): void {
		$this->scanner->initScanner('test.txt');
		$this->scanner->onAsyncData($scanData);
		$status = $this->scanner->completeAsyncScan();
		self::assertEquals($expectedStatus, $status->getNumericStatus());
	}

	/**
	 * @dataProvider providesScanData
	 * @throws InitException
	 */
	public function testScannerScan(int $expectedStatus, string $scanData): void {
		$status = $this->scanner->scan(new Content('test.txt', $scanData, 5));
		self::assertEquals($expectedStatus, $status->getNumericStatus());
	}

	public function providesScanData(): array {
		return [
			'eicar' => [Status::SCANRESULT_INFECTED, 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'],
			'clean data' => [Status::SCANRESULT_CLEAN, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
		];
	}
}
