<?php

namespace OCA\Files_Antivirus\Migrations;
use OCA\Files_Antivirus\Db\RuleMapper;
use OCP\Migration\IOutput;
use OCP\Migration\ISimpleMigration;

/** Populate statuses */
class Version20170808222334 implements ISimpleMigration {
	private $ruleMapper;
	
	public function __construct(RuleMapper $ruleMapper){
		$this->ruleMapper = $ruleMapper;
	}

	public function run(IOutput $out) {
		$rules = $this->ruleMapper->findAll();
		if (!count($rules)) {
			$this->ruleMapper->populate();
		}
	}
}
