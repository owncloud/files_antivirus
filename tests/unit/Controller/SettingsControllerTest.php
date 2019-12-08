<?php

/**
 * Copyright (c) 2016 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Controller;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\ScannerFactory;
use OCA\Files_Antivirus\Tests\unit\TestBase;
use OCA\Files_Antivirus\Controller\SettingsController;
use OCP\IRequest;
use OCP\IL10N;

class AvirWrapperTest extends TestBase {

	/** @var IRequest | \PHPUnit\Framework\MockObject\MockObject */
	protected $request;

	/** @var IL10N | \PHPUnit\Framework\MockObject\MockObject */
	protected $l10n;

	/** @var AppConfig | \PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var ScannerFactory | \PHPUnit\Framework\MockObject\MockObject */
	protected $scannerFactory;

	public function setUp(): void {
		parent::setUp();
		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();

		$this->l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();

		$this->config = $this->getMockBuilder(AppConfig::class)
			->disableOriginalConstructor()
			->setMethods(['setter', 'getAppValue'])
			->getMock();

		$this->scannerFactory = $this->getMockBuilder(ScannerFactory::class)
			->disableOriginalConstructor()
			->getMock();
	}

	public function testSaveExecutable() {
		$this->config->expects($this->atLeast(1))
			->method('setter')
			->withConsecutive(
				['av_mode', ['executable']],
				['av_cmd_options', ['--fdpass']],
				['av_path', ['/usr/bin/clamav']]
			);

		$settings = new SettingsController($this->request, $this->config, $this->scannerFactory, $this->l10n);

		$settings->save(
			'executable',
			null,
			null,
			null,
			'--fdpass',
			'/usr/bin/clamav',
			'delete',
			100,
			800
		);
	}

	public function testSaveSocket() {
		$this->config->expects($this->atLeast(1))
			->method('setter')
			->withConsecutive(
				['av_mode', ['socket']],
				['av_socket', ['/var/run/clamd.sock']]
			);

		$settings = new SettingsController($this->request, $this->config, $this->scannerFactory, $this->l10n);

		$settings->save(
			'socket',
			'/var/run/clamd.sock',
			null,
			null,
			null,
			null,
			'delete',
			100,
			800
		);
	}

	public function testSaveDaemon() {
		$avirHost = \getenv('AVIR_HOST');
		if ($avirHost === false) {
			$avirHost = '127.0.0.1';
		}
		$this->config->expects($this->atLeast(1))
			->method('setter')
			->withConsecutive(
				['av_mode', ['daemon']],
				['av_port', ['90']],
				['av_host', [$avirHost]]
			);

		$settings = new SettingsController($this->request, $this->config, $this->scannerFactory, $this->l10n);

		$settings->save(
			'daemon',
			null,
			$avirHost,
			'90',
			null,
			null,
			'delete',
			100,
			800
		);
	}
}
