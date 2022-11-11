<?php
/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OC\Files\View;
use OC\User\LoginException;
use OCA\Files_Antivirus\Item;
use OCP\AppFramework\QueryException;
use OCP\Files\NotFoundException;
use Test\Util\User\Dummy;

class ItemTest extends TestBase {
	public const UID = 'testo';
	public const PWD = 'test';
	public const CONTENT = 'LoremIpsum';

	protected View $view;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		\OC_User::clearBackends();
		\OC_User::useBackend(new Dummy());
	}

	/**
	 * @throws LoginException
	 * @throws QueryException
	 * @throws \Exception
	 */
	public function setUp(): void {
		parent::setUp();

		//login
		if (!\OC::$server->getUserManager()->get(self::UID)) {
			\OC::$server->getUserManager()->createUser(self::UID, self::PWD);
		}
		\OC::$server->getUserSession()->login(self::UID, self::PWD);
		\OC::$server->getSession()->set('user_id', self::UID);
		\OC::$server->getUserFolder(self::UID);
		\OC_Util::setupFS(self::UID);
		
		$config = $this->container->query('AppConfig');
		$oldLimit = $config->getAvMaxFileSize();
		$config->setAvMaxFileSize(1);
		$this->view = new View('/' . self::UID . '/files');
		$this->view->file_put_contents('file1', self::CONTENT);
		$config->setAvMaxFileSize($oldLimit);
	}

	/**
	 * @throws NotFoundException
	 * @throws QueryException
	 */
	public function testRead(): void {
		$item = new Item($this->l10n, $this->view, '/file1');
		$this->assertTrue($item->isValid());
		
		$chunk = $item->fread();
		$this->assertEquals(self::CONTENT, $chunk);
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		\OC_Util::tearDownFS();
		\OC::$server->getUserManager()->get(self::UID)->delete();
		\OC_User::clearBackends();
	}
}
