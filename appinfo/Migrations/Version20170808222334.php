<?php
/**
 * Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Migrations;
use OCA\Files_Antivirus\Db\RuleMapper;
use OCP\Migration\IOutput;
use OCP\Migration\ISimpleMigration;

/**
 * Populate statuses
 */
class Version20170808222334 implements ISimpleMigration {
	private $ruleMapper;

	/**
	 * Version20170808222334 constructor.
	 *
	 * @param RuleMapper $ruleMapper
	 */
	public function __construct(RuleMapper $ruleMapper) {
		$this->ruleMapper = $ruleMapper;
	}

	/**
	 * @param IOutput $out
	 *
	 * @return void
	 */
	public function run(IOutput $out) {
		$rules = $this->ruleMapper->findAll();
		if (!\count($rules)) {
			$this->ruleMapper->populate();
		}
	}
}
