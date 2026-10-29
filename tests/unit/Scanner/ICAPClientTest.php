<?php

namespace OCA\Files_Antivirus\Tests\unit\Scanner;

use OCA\Files_Antivirus\Scanner\ICAPClient;
use Test\TestCase;

class ICAPClientTest extends TestCase {
	public function testGetRequestStripsControlCharacters(): void {
		$client = new ICAPClient("icap.example.org\r\nX-Host: a", 1344);
		$request = $client->getRequest(
			'REQMOD',
			"avscan\r\nX-Extra: 1\r\n",
			[],
			["X-Client-IP" => "127.0.0.1\nX-Other: 2"]
		);

		$lines = \explode("\r\n", $request);
		self::assertSame('REQMOD icap://icap.example.orgX-Host: a/avscanX-Extra: 1 ICAP/1.0', $lines[0]);
		self::assertSame('X-Client-IP: 127.0.0.1X-Other: 2', $lines[1]);
		self::assertSame('Host: icap.example.orgX-Host: a', $lines[2]);
		self::assertStringNotContainsString("\n", \str_replace("\r\n", '', $request));
		self::assertStringNotContainsString("\r", \str_replace("\r\n", '', $request));
	}

	public function providesControlCharacters(): array {
		return [
			'NUL' => ["\x00"],
			'tab' => ["\t"],
			'DEL' => ["\x7f"],
			'lone CR' => ["\r"],
			'lone LF' => ["\n"],
			'CRLF' => ["\r\n"],
		];
	}

	/**
	 * @dataProvider providesControlCharacters
	 */
	public function testGetRequestStripsEachControlCharacter(string $char): void {
		$client = new ICAPClient("icap{$char}.example.org", 1344);
		$request = $client->getRequest(
			"RESP{$char}MOD",
			"av{$char}scan",
			[],
			["X-{$char}Name" => "va{$char}lue"]
		);

		self::assertSame(
			"RESPMOD icap://icap.example.org/avscan ICAP/1.0\r\n"
			. "X-Name: value\r\n"
			. "Host: icap.example.org\r\n"
			. "User-Agent: ownCloud-icap-client/0.1.0\r\n"
			. "Connection: close\r\n"
			. "\r\n",
			$request
		);
	}

	public function testGetRequestKeepsRegularValues(): void {
		$client = new ICAPClient('icap.example.org', 1344);
		$request = $client->getRequest('OPTIONS', 'avscan');

		self::assertStringStartsWith("OPTIONS icap://icap.example.org/avscan ICAP/1.0\r\nHost: icap.example.org\r\n", $request);
	}
}
