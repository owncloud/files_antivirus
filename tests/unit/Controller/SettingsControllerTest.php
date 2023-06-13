<?php

/**
 * Copyright (c) 2021 Viktar Dubiniuk <dubiniuk@owncloud.com>
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
use PHPUnit\Framework\MockObject\MockObject;

class SettingsControllerTest extends TestBase {
	/** @var IRequest | MockObject
	 */
	protected $request;

	/** @var IL10N | MockObject */
	protected $l10n;

	/** @var AppConfig | MockObject */
	protected $config;

	/** @var ScannerFactory | MockObject */
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
			->onlyMethods(['setter', 'getAppValue'])
			->getMock();

		$this->scannerFactory = $this->getMockBuilder(ScannerFactory::class)
			->disableOriginalConstructor()
			->getMock();
	}

	public function testSaveExecutable(): void {
		$this->config->expects(self::atLeast(1))
			->method('setter')
			->withConsecutive(
				['av_infected_action', ['delete']],
				['av_stream_max_length', [100]],
				['av_max_file_size', [800]],
				['av_mode', ['executable']],
				['av_scan_background', [true]],
			);

		$settings = new SettingsController($this->request, $this->config, $this->scannerFactory, $this->l10n);
		$settings->save(
			'executable',
			null,
			null,
			null,
			'delete',
			100,
			800,
			'',
			'',
			true
		);
	}

	public function testSaveSocket(): void {
		$this->config->expects(self::atLeast(1))
			->method('setter')
			->withConsecutive(
				['av_socket', ['/var/run/clamd.sock']],
				['av_infected_action', ['delete']],
				['av_stream_max_length', [100]],
				['av_max_file_size', [800]],
				['av_mode', ['socket']],
				['av_scan_background', [false]],
			);

		$settings = new SettingsController($this->request, $this->config, $this->scannerFactory, $this->l10n);

		$settings->save(
			'socket',
			'/var/run/clamd.sock',
			null,
			null,
			'delete',
			100,
			800,
			'',
			'',
			false
		);
	}

	public function testSaveDaemon(): void {
		$avirHost = \getenv('AVIR_HOST');
		if ($avirHost === false) {
			$avirHost = '127.0.0.1';
		}
		$this->config->expects(self::atLeast(1))
			->method('setter')
			->withConsecutive(
				['av_port', ['90']],
				['av_host', [$avirHost]],
				['av_infected_action', ['delete']],
				['av_stream_max_length', [100]],
				['av_max_file_size', [800]],
				['av_mode', ['daemon']]
			);

		$settings = new SettingsController($this->request, $this->config, $this->scannerFactory, $this->l10n);
		$settings->save(
			'daemon',
			null,
			$avirHost,
			'90',
			'delete',
			100,
			800,
			'',
			'',
			true
		);
	}
}
