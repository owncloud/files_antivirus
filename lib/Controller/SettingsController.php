<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2015-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Controller;

use OCA\Files_Antivirus\ScannerFactory;
use \OCP\AppFramework\Controller;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCA\Files_Antivirus\AppConfig;

use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Http\JSONResponse;

class SettingsController extends Controller {

	/**
	 * @var AppConfig
	 */
	private $settings;

	/**
	 * @var ScannerFactory
	 */
	private $scannerFactory;
	
	/**
	 * @var IL10N
	 */
	private $l10n;

	/**
	 * SettingsController constructor.
	 *
	 * @param IRequest $request
	 * @param AppConfig $appConfig
	 * @param ScannerFactory $scannerFactory
	 * @param IL10N $l10n
	 */
	public function __construct(IRequest $request,
		AppConfig $appConfig, ScannerFactory $scannerFactory, IL10N $l10n
	) {
		$this->settings = $appConfig;
		$this->scannerFactory = $scannerFactory;
		$this->l10n = $l10n;
	}
	
	/**
	 * Print config section
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		$data = $this->settings->getAllValues();
		return new TemplateResponse('files_antivirus', 'settings', $data, 'blank');
	}

	/**
	 * Save Parameters
	 *
	 * @param string $avMode - antivirus mode
	 * @param string $avSocket - path to socket (Socket mode)
	 * @param string $avHost - antivirus url
	 * @param int $avPort - port
	 * @param string $avCmdOptions - extra command line options
	 * @param string $avPath - path to antivirus executable (Executable mode)
	 * @param string $avInfectedAction - action performed on infected files
	 * @param int $avStreamMaxLength - reopen socket after bytes
	 * @param int $avMaxFileSize - file size limit
	 *
	 * @return JSONResponse
	 */
	public function save($avMode, $avSocket, $avHost, $avPort,
		$avCmdOptions, $avPath, $avInfectedAction, $avStreamMaxLength, $avMaxFileSize
	) {
		$this->settings->setAvMode($avMode);
		if ($avMode === 'executable') {
			$this->settings->setAvCmdOptions($avCmdOptions);
			$this->settings->setAvPath($avPath);
		} elseif ($avMode === 'daemon') {
			$this->settings->setAvPort($avPort);
			$this->settings->setAvHost($avHost);
		} elseif ($avMode === 'socket') {
			$this->settings->setAvSocket($avSocket);
		}

		$this->settings->setAvInfectedAction($avInfectedAction);
		$this->settings->setAvStreamMaxLength($avStreamMaxLength);
		$this->settings->setAvMaxFileSize($avMaxFileSize);

		$connectionStatus = \intval(
			$this->scannerFactory->testConnection($this->settings)
		);

		return new JSONResponse(
			['data' =>
				['message' =>
					(string) $this->l10n->t('Saved')
				],
				'connection' => $connectionStatus,
				'status' => 'success',
				'settings' => $this->settings->getAllValues()
			]
		);
	}
}
