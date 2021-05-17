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

namespace OCA\Files_Antivirus\Dav;

use OCA\DAV\Upload\FutureFile;
use OCA\Files_Antivirus\AppInfo\Application;
use OCA\Files_Antivirus\Resource;
use OCA\Files_Antivirus\Status;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

/**
 * Sabre plugin for the the antivirus
 */
class AntivirusPlugin extends ServerPlugin {
	const NS_OWNCLOUD = 'http://owncloud.org/ns';

	/**
	 * @var \Sabre\DAV\Server $server
	 */
	private $davServer;

	/**
	 * @var Application
	 */
	private $application;

	/**
	 * Constructor
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		$this->application = $application;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 *
	 * @return void
	 */
	public function initialize(Server $server) {
		$this->davServer = $server;
		$this->davServer->on('beforeMove', [$this, 'beforeMove'], 1);
		$this->davServer->on('beforeCreateFile', [$this, 'beforeCreateFile']);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool|void
	 * @throws \Sabre\DAV\Exception\NotFound
	 */
	public function beforeMove($source, $destination) {
		$sourceNode = $this->davServer->tree->getNodeForPath($source);
		if ($sourceNode instanceof FutureFile) {
			$finalSize = $sourceNode->getSize();
			$requestHelper = $this->application->getContainer()->query('RequestHelper');
			$requestHelper->setSizeForPath($destination, $finalSize);
		}
		return true;
	}

	/**
	 * This method is triggered before a new file is created.
	 *
	 * @param string $path
	 * @param resource $data
	 * @param INode $parentNode
	 * @param bool $modified should be set to true, if this event handler
	 *                           changed &$data
	 */
	public function beforeCreateFile($path, &$data, INode $parentNode, &$modified) {
		$container = $this->application->getContainer();
		$server = $container->getServer();
		// Scan a public upload
		if ($server->getUserSession()->getUser() === null) {
			$appConfig = $container->query('AppConfig');
			$scannerFactory = $container->query('ScannerFactory');
			$scanner = $scannerFactory->getScanner();
			$status = $scanner->scan(new Resource($data, $appConfig->getAvChunkSize()));
			if (\intval($status->getNumericStatus()) === Status::SCANRESULT_INFECTED) {
				$details = $status->getDetails();
				$server->getLogger()->warning(
					"Infected file deleted after uploading to the public folder. $details Path: $path",
					['app' => 'files_antivirus']
				);
				throw new Forbidden(
					$container->query('L10N')->t(
						'Virus %s is detected in the file. Upload cannot be completed.',
						$status->getDetails()
					), false
				);
			}
		}
		\rewind($data);
		return true;
	}
}
