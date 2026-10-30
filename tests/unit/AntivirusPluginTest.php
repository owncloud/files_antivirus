<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OCA\Files_Antivirus\AppInfo\Application;
use OCA\Files_Antivirus\Dav\AntivirusPlugin;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;
use Sabre\DAV\ICollection;
use Sabre\DAV\IFile;

class AntivirusPluginTest extends TestBase {
	/**
	 * @var AntivirusPlugin
	 */
	private $plugin;

	public function setUp(): void {
		parent::setUp();
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->plugin = new AntivirusPlugin(
			$this->createMock(Application::class),
			$userSession,
			$this->createMock(ILogger::class)
		);
	}

	/**
	 * The CalDAV/CardDAV plugins replace the body stream with a string
	 * before this plugin sees it
	 */
	public function testBeforeCreateFileAcceptsStringData(): void {
		$data = "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n";
		$modified = false;
		$result = $this->plugin->beforeCreateFile(
			'calendars/user/personal/event.ics',
			$data,
			$this->createMock(ICollection::class),
			$modified
		);
		$this->assertTrue($result);
		$this->assertSame("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n", $data);
	}

	public function testBeforeWriteContentAcceptsStringData(): void {
		$data = "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n";
		$modified = false;
		$result = $this->plugin->beforeWriteContent(
			'calendars/user/personal/event.ics',
			$this->createMock(IFile::class),
			$data,
			$modified
		);
		$this->assertTrue($result);
		$this->assertSame("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n", $data);
	}

	public function testBeforeCreateFileRewindsStream(): void {
		$data = \fopen('php://memory', 'r+');
		\fwrite($data, 'content');
		$modified = false;
		$this->plugin->beforeCreateFile(
			'files/user/file.txt',
			$data,
			$this->createMock(ICollection::class),
			$modified
		);
		$this->assertSame(0, \ftell($data));
	}
}
