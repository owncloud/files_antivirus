<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Dav;

use OCA\DAV\Upload\FutureFile;
use OCA\Files_Antivirus\AppInfo\Application;
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
	private $server;

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
		$this->server = $server;
		$this->server->on('beforeMove', [$this, 'beforeMove'], 1);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool|void
	 * @throws \Sabre\DAV\Exception\NotFound
	 */
	public function beforeMove($source, $destination) {
		$sourceNode = $this->server->tree->getNodeForPath($source);
		if ($sourceNode instanceof FutureFile) {
			$finalSize = $sourceNode->getSize();
			$requestHelper = $this->application->getContainer()->query('RequestHelper');
			$requestHelper->setSizeForPath($destination, $finalSize);
		}
		return true;
	}
}
