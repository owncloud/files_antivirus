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
use OCP\AppFramework\QueryException;
use OCP\ILogger;
use OCP\IUserSession;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

/**
 * Sabre plugin for the antivirus
 */
class AntivirusPlugin extends ServerPlugin {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	/**
	 * @var Server
	 */
	private $davServer;

	/**
	 * @var Application
	 */
	private $application;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var ILogger
	 */
	private $logger;

	public function __construct(Application $application, IUserSession $userSession, ILogger $logger) {
		$this->application = $application;
		$this->userSession = $userSession;
		$this->logger = $logger;
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
		$this->davServer->on('beforeWriteContent', [$this, 'beforeWriteContent']);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool
	 * @throws QueryException
	 * @throws NotFound
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
	 * @return bool|null
	 * @throws Forbidden
	 * @throws QueryException
	 */
	public function beforeCreateFile(string $path, &$data, INode $parentNode, &$modified) {
		// Scan a public upload
		if ($this->userSession->getUser() === null) {
			$this->scanPublicUpload($path, $data);
		}
		\rewind($data);
		return true;
	}

	/**
	 * This method is triggered before a content written (but not for new files).
	 *
	 * @param string $path
	 * @param INode $node
	 * @param resource $data
	 * @param bool $modified should be set to true, if this event handler
	 *                           changed &$data
	 * @return bool|null
	 * @throws Forbidden
	 * @throws QueryException
	 */
	public function beforeWriteContent($path, INode $node, &$data, &$modified) {
		// Scan a public upload
		if ($this->userSession->getUser() === null) {
			$this->scanPublicUpload($path, $data);
		}
		\rewind($data);
		return true;
	}

	/**
	 * @param string $path
	 * @param resource $data
	 * @throws Forbidden
	 * @throws QueryException
	 */
	private function scanPublicUpload(string $path, &$data): void {
		$container = $this->application->getContainer();
		$appConfig = $container->query('AppConfig');
		$scannerFactory = $container->query('ScannerFactory');
		$scanner = $scannerFactory->getScanner();
		$status = $scanner->scan(new Resource(basename($path), $data, $appConfig->getAvChunkSize()));
		if ((int)$status->getNumericStatus() === Status::SCANRESULT_INFECTED) {
			$details = $status->getDetails();
			$this->logger->warning(
				"Infected file deleted after uploading to the public folder. $details Path: $path",
				['app' => 'files_antivirus']
			);
			throw new Forbidden(
				$container->query('L10N')->t(
					'Virus %s is detected in the file. Upload cannot be completed.',
					$status->getDetails()
				),
				false
			);
		}
	}
}
