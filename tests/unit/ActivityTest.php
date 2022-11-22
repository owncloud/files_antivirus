<?php
/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit;

use OCA\Files_Antivirus\Activity;
use OC\L10N\Factory;
use OCP\IURLGenerator;

class ActivityTest extends TestBase {
	/**
	 * @var Activity
	 */
	protected $activity;
	
	public function setUp(): void {
		parent::setUp();
		$langFactory = $this->getMockBuilder(Factory::class)
				->disableOriginalConstructor()
				->getMock()
		;
		
		$urlGenerator = $this->getMockBuilder(IURLGenerator::class)
				->disableOriginalConstructor()
				->getMock()
		;
		
		$this->activity = new Activity($langFactory, $urlGenerator);
	}
	
	public function testGetTypeIcon(): void {
		$this->assertFalse(
			$this->activity->getTypeIcon(null)
		);
		
		$this->assertEquals('icon-info', $this->activity->getTypeIcon(Activity::TYPE_VIRUS_DETECTED));
	}
	
	public function testGetSpecialParameterList(): void {
		$this->assertFalse(
			$this->activity->getSpecialParameterList(null, null)
		);
	}
	
	public function testGetGroupParameter(): void {
		$this->assertFalse(
			$this->activity->getGroupParameter(null)
		);
	}
}
