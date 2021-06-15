<?php
/**
 * Copyright (c) 2015 Viktar Dubiniuk <dubiniuk@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Antivirus\Tests\unit\Db;

use OCA\Files_Antivirus\Db\Rule;
use OCA\Files_Antivirus\Status;
use OCA\Files_Antivirus\Tests\unit\TestBase;

class RuleTest extends TestBase {
	public function testJsonSerialize() {
		$data = [
			'groupId' => 0,
			'statusType' => Rule::RULE_TYPE_CODE,
			'result' => 0,
			'match' => '',
			'description' => "",
			'status' => Status::SCANRESULT_CLEAN
		];
		$expected = [
			'group_id' => 0,
			'status_type' => Rule::RULE_TYPE_CODE,
			'result' => 0,
			'match' => '',
			'description' => "",
			'status' => Status::SCANRESULT_CLEAN
		];
		
		$rule = Rule::fromParams($data);
		$actual = $rule->jsonSerialize();
		$this->assertArrayHasKey('id', $actual);
		unset($actual['id']);
		$this->assertEquals(
			$expected,
			$actual
		);
	}
}
