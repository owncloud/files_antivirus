<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2021
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus;

use \OCP\IConfig;
use OCP\ILogger;
use OCP\License\ILicenseManager;

/**
 * @method string getAvMode()
 * @method string getAvSocket()
 * @method string getAvHost()
 * @method int getAvPort()
 * @method int getAvMaxFileSize()
 * @method int getAvStreamMaxLength()
 * @method string getAvCmdOptions()
 * @method string getAvPath()
 * @method string getAvInfectedAction()
 * @method string getAvScanBackground()
 * @method string getAvRequestService()
 * @method string getAvResponseHeader()
 *
 * @method null setAvMode(string $avMode)
 * @method null setAvSocket(string $avsocket)
 * @method null setAvHost(string $avHost)
 * @method null setAvPort(int $avPort)
 * @method null setAvMaxFileSize(int $fileSize)
 * @method null setAvStreamMaxLength(int $streamMaxLength)
 * @method null setAvCmdOptions(string $avCmdOptions)
 * @method null setAvPath(string $avPath)
 * @method null setAvInfectedAction(string $avInfectedAction)
 * @method null setAvScanBackground(string $scanBackground)
 * @method null setAvRequestService($reqService)
 * @method null setAvResponseHeader($respHeader)
 */

class AppConfig {
	private $appName = 'files_antivirus';

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var ILicenseManager
	 */
	private $licenseManager;

	/**
	 * @var ILogger
	 */
	private $logger;

	private $defaults = [
		'av_mode' => 'executable',
		'av_socket' => '/var/run/clamav/clamd.ctl',
		'av_host' => 'localhost',
		'av_port' => '3310',
		'av_cmd_options' => '',
		'av_path' => '/usr/bin/clamscan',
		'av_max_file_size' => -1,
		'av_stream_max_length' => '26214400',
		'av_infected_action' => 'only_log',
		'av_scan_background' => 'true',
		'av_request_service' => 'avscan',
		'av_response_header' => 'X-Infection-Found',
	];

	/**
	 * AppConfig constructor.
	 *
	 * @param IConfig $config
	 * @param ILicenseManager $licenseManager
	 * @param ILogger $logger
	 */
	public function __construct(IConfig $config, ILicenseManager $licenseManager, ILogger $logger) {
		$this->config = $config;
		$this->licenseManager = $licenseManager;
		$this->logger = $logger;
	}

	public function getAvChunkSize() {
		// See http://php.net/manual/en/function.stream-wrapper-register.php#74765
		// and \OC_Helper::streamCopy
		return 8192;
	}

	/**
	 * Get full commandline
	 *
	 * @return string
	 */
	public function getCmdline() {
		$avCmdOptions = $this->getAvCmdOptions();

		$shellArgs = [];
		if ($avCmdOptions) {
			$shellArgs = \explode(',', $avCmdOptions);
			$shellArgs = \array_map(
				function ($i) {
					return \escapeshellarg($i);
				},
				$shellArgs
			);
		}

		$preparedArgs = '';
		if (\count($shellArgs)) {
			$preparedArgs = \implode(' ', $shellArgs);
		}
		return $preparedArgs;
	}

	/**
	 * Get all setting values as an array
	 *
	 * @return array
	 */
	public function getAllValues() {
		$keys = \array_keys($this->defaults);
		$values = \array_map([$this, 'getAppValue'], $keys);
		$preparedKeys = \array_map([$this, 'camelCase'], $keys);
		return \array_combine($preparedKeys, $values);
	}

	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue($key) {
		$defaultValue = null;
		if (\array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}
		if ($key === 'av_path' || $key === 'av_cmd_options') {
			return $this->config->getSystemValue($this->appName . "." . $key, $defaultValue);
		}
		$value = $this->config->getAppValue($this->appName, $key, $defaultValue);
		try {
			$this->validateValue($key, $value);
		} catch (\UnexpectedValueException $e) {
			$this->logger->error('No valid license found for icap scanner, resetting mode to executable');
			$value = 'executable';
		}
		return  $value;
	}

	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 *
	 * @throws \UnexpectedValueException
	 */
	public function setAppValue($key, $value) {
		if ($key === 'av_path' || $key === 'av_cmd_options') {
			return;
		}
		$this->validateValue($key, $value);
		$this->config->setAppValue($this->appName, $key, $value);
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 *
	 * @throws \UnexpectedValueException
	 */
	public function validateValue($key, $value) {
		if (
			$key === 'av_mode'
			&& $value === 'icap'
			&& !$this->licenseManager->checkLicenseFor($this->appName, ["disableApp" => false])
		) {
			$this->logger->error('No valid license found for icap scanner');
			throw new \UnexpectedValueException("No valid license found for icap scanner mode");
		}
	}

	/**
	 * Set a value with magic __call invocation
	 *
	 * @param string $key
	 * @param array $args
	 *
	 * @throws \BadFunctionCallException
	 */
	protected function setter($key, $args) {
		if (\array_key_exists($key, $this->defaults)) {
			$this->setAppValue($key, $args[0]);
		} else {
			throw new \BadFunctionCallException($key . ' is not a valid key');
		}
	}

	/**
	 * Get a value with magic __call invocation
	 *
	 * @param string $key
	 *
	 * @return string
	 *
	 * @throws \BadFunctionCallException
	 */
	protected function getter($key) {
		if (\array_key_exists($key, $this->defaults)) {
			$value = $this->getAppValue($key);
			if ($key === 'av_max_file_size') {
				return (int) $value;
			}
			return $value;
		} else {
			throw new \BadFunctionCallException($key . ' is not a valid key');
		}
	}

	/**
	 * Translates property_name into propertyName
	 *
	 * @param string $property
	 *
	 * @return string
	 */
	protected function camelCase($property) {
		$split = \explode('_', $property);
		$ucFirst = \implode('', \array_map('ucfirst', $split));
		$camelCase = \lcfirst($ucFirst);
		return $camelCase;
	}

	/**
	 * Does all the someConfig to some_config magic
	 *
	 * @param string $property
	 *
	 * @return string
	 */
	protected function propertyToKey($property) {
		$parts = \preg_split('/(?=[A-Z])/', $property);
		$column = null;

		foreach ($parts as $part) {
			if ($column === null) {
				$column = $part;
			} else {
				$column .= '_' . \lcfirst($part);
			}
		}

		return $column;
	}

	/**
	 * Get/set an option value by calling getSomeOption method
	 *
	 * @param string $methodName
	 * @param array $args
	 *
	 * @return string|null
	 *
	 * @throws \BadFunctionCallException
	 */
	public function __call($methodName, $args) {
		$attr = \lcfirst(\substr($methodName, 3));
		$key = $this->propertyToKey($attr);
		if (\strpos($methodName, 'set') === 0) {
			$this->setter($key, $args);
		} elseif (\strpos($methodName, 'get') === 0) {
			return $this->getter($key);
		} else {
			throw new \BadFunctionCallException(
				$methodName . ' does not exist'
			);
		}
	}
}
