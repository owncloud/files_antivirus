<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2014-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Scanner;

use OCA\Files_Antivirus\AppConfig;
use OCP\IL10N;
use OCP\ILogger;

class Local extends AbstractScanner {
	/**
	 * @var string
	 */
	protected $avPath;
	
	/**
	 * STDIN and STDOUT descriptors
	 *
	 * @var array of resources
	 */
	private $pipes = [];
	
	/**
	 * Process handle
	 *
	 * @var resource
	 */
	private $process;

	/**
	 * Local constructor.
	 *
	 * @throws InitException
	 */
	public function __construct(AppConfig $config, ILogger $logger, IL10N $l10n) {
		parent::__construct($config, $logger, $l10n);

		// get the path to the executable
		$this->avPath = \escapeshellcmd($this->appConfig->getAvPath());

		if (!\file_exists($this->avPath)) {
			throw new InitException(
				\sprintf(
					'The antivirus executable could not be found at path "%s"',
					$this->avPath
				)
			);
		}
	}

	/**
	 * @throws InitException
	 */
	public function initScanner(string $fileName): void {
		parent::initScanner($fileName);
		
		// using 2>&1 to grab the full command-line output.
		$cmd = $this->avPath . " " . $this->appConfig->getCmdline() . " - 2>&1";
		$descriptorSpec = [
			0 => ["pipe","r"], // STDIN
			1 => ["pipe","w"]  // STDOUT
		];
		
		$this->process = \proc_open($cmd, $descriptorSpec, $this->pipes);
		if (!\is_resource($this->process)) {
			throw new InitException(
				\sprintf('Error starting process "%s"', $cmd)
			);
		}
		$this->writeHandle = $this->pipes[0];
	}
	
	public function shutdownScanner(): void {
		@\fclose($this->pipes[0]);
		$output = \stream_get_contents($this->pipes[1]);
		@\fclose($this->pipes[1]);
		
		$result = \proc_close($this->process);
		$this->logger->debug(
			'Exit code :: ' . $result . ' Response :: ' . $output,
			['app' => 'files_antivirus']
		);
		$this->status->parseResponse($output, $result);
	}
}
