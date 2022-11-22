<?php

namespace OCA\Files_Antivirus\Tests\Integration;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\Content;
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

	protected function setUp(): void {
		parent::setUp();

		$config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->addMethods([
				'getAvHost',
				'getAvPort',
				'getAvRequestService',
				'getAvResponseHeader',
			])
			->getMock();

		$logger = $this->createMock(ILogger::class);
		$l10n = $this->createMock(IL10N::class);

		# for local testing replace 'icap' with the ip of the clamav instance
		$config->method('getAvHost')->willReturn('icap');
		$config->method('getAvPort')->willReturn(1344);
		$config->method('getAvRequestService')->willReturn('avscan');
		$config->method('getAvResponseHeader')->willReturn('X-Infection-Found');

		$this->scanner = new ICAPScanner($config, $logger, $l10n);
	}

	/**
	 * @dataProvider providesScanData
	 * @throws InitException
	 */
	public function testScannerAsync(int $expectedStatus, string $scanData): void {
		$this->scanner->initScanner('test.tst');
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
