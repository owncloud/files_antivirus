<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OCA\Files_Antivirus\AppConfig;
use OCA\Files_Antivirus\AppInfo\Application;
use OCA\Files_Antivirus\Dav\AntivirusPlugin;
use OCA\Files_Antivirus\IScannable;
use OCA\Files_Antivirus\Scanner\IScanner;
use OCA\Files_Antivirus\ScannerFactory;
use OCA\Files_Antivirus\Status;
use OCP\AppFramework\IAppContainer;
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

	public function testBeforeWriteContentRewindsStream(): void {
		$data = \fopen('php://memory', 'r+');
		\fwrite($data, 'content');
		$modified = false;
		$this->plugin->beforeWriteContent(
			'files/user/file.txt',
			$this->createMock(IFile::class),
			$data,
			$modified
		);
		$this->assertSame(0, \ftell($data));
	}

	public function testPublicUploadScansStringData(): void {
		$scanned = null;
		$status = Status::create(Status::SCANRESULT_CLEAN);
		$scanner = $this->createMock(IScanner::class);
		$scanner->method('scan')->willReturnCallback(
			function (IScannable $item) use (&$scanned, $status) {
				$scanned = '';
				while (($chunk = $item->fread()) !== false) {
					$scanned .= $chunk;
				}
				return $status;
			}
		);
		$scannerFactory = $this->createMock(ScannerFactory::class);
		$scannerFactory->method('getScanner')->willReturn($scanner);
		$appConfig = $this->createMock(AppConfig::class);
		$appConfig->method('getAvChunkSize')->willReturn(4);
		$container = $this->createMock(IAppContainer::class);
		$container->method('query')->willReturnMap([
			['AppConfig', $appConfig],
			['ScannerFactory', $scannerFactory],
		]);
		$application = $this->createMock(Application::class);
		$application->method('getContainer')->willReturn($container);
		$plugin = new AntivirusPlugin(
			$application,
			$this->createMock(IUserSession::class),
			$this->createMock(ILogger::class)
		);

		$data = "BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n";
		$modified = false;
		$this->assertTrue($plugin->beforeCreateFile(
			'calendars/user/personal/event.ics',
			$data,
			$this->createMock(ICollection::class),
			$modified
		));
		$this->assertSame("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n", $scanned);
	}
}
