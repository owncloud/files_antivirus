<?php

namespace OCA\Files_Antivirus\Tests\unit\Scanner;

use Generator;
use JsonException;
use OCA\Files_Antivirus\Scanner\ICAPResponseAnalyser;
use OCA\Files_Antivirus\Status;
use Test\TestCase;

class ICAPResponseTest extends TestCase {
	/**
	 * @dataProvider providesScanData
	 * @throws JsonException
	 */
	public function test(?int $expectedStatus, ?string $expectedVirusName, string $response): void {
		$response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
		$analyser = new ICAPResponseAnalyser('X-Virus-Name');
		$status = $analyser->analyseResponse($response);

		self::assertEquals($expectedStatus, $status[0]);
		self::assertEquals($expectedVirusName, $status[1] ?? null);
	}

	public function providesScanData(): Generator {
		yield 'McAfee 11' => [Status::SCANRESULT_INFECTED, 'EICAR test file', '{"protocol":{"protocolVersion":"1.0","code":100,"status":"Continue"},"headers":{"ISTag":"\"008040-000000-10698-116696-00\"","ICAP\\\/1.0 200 OK":"","Encapsulated":"res-hdr=0, res-body=121"},"body":{"res-hdr":{"HTTP_STATUS":"X-Media-Type: text\\\/plain","X-Media-Type":"text\\\/plain","X-Virus-Name":"EICAR test file","X-Block-Reason":"Malware found","X-WWBlockResult":"80","HTTP\\\/1.1 4":""}}}'];
		yield 'McAfee 10' => [Status::SCANRESULT_INFECTED, null, '{"protocol":{"protocolVersion":"1.0","code":200,"status":"OK"},"headers":{"ISTag":"\\"00007468-8.39.102-00010079\\"","Encapsulated":"res-hdr=0, res-body=114"},"body":{"res-hdr":{"HTTP_STATUS":"403 VirusFound"," 403 VirusFound":"","Content-Type":"text\\\/html","Cache-Control":"no-cache","Content-Length":"2682","X-Frame-Options":"deny"}}}'];
	}
}
