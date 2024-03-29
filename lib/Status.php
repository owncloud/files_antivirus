<?php
/**
 * ownCloud - files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2014-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus;

use OCP\AppFramework\QueryException;
use OCP\Files\NotFoundException;

class Status {
	/*
	 *  The file was not checked (e.g. because the AV daemon wasn't running).
	 */
	public const SCANRESULT_UNCHECKED = -1;

	/*
	 *  The file was checked and found to be clean.
	 */
	public const SCANRESULT_CLEAN = 0;

	/*
	 *  The file was checked and found to be infected.
	 */
	public const SCANRESULT_INFECTED = 1;

	/*
	 * Should be SCANRESULT_UNCHECKED | SCANRESULT_INFECTED | SCANRESULT_CLEAN
	 */
	protected $numericStatus;

	/*
	 * Virus name or error message
	 */
	protected $details = "";

	protected $ruleMapper;

	public function __construct() {
		$this->numericStatus = self::SCANRESULT_UNCHECKED;
		/** @phan-suppress-next-line PhanDeprecatedFunction */
		$this->ruleMapper = new Db\RuleMapper(\OC::$server->getDb());
	}

	/**
	 * Creates an instance of class Status with given status code and optional detailed information.
	 * External scanner implementations are using this to create a status.
	 * @param int $status
	 * @param string $details
	 * @return static
	 */
	public static function create(int $status, string $details = '') {
		$s = new static();
		$s->numericStatus = $status;
		$s->details = $details;
		return $s;
	}

	/**
	 * Get scan status as integer
	 *
	 * @return int
	 */
	public function getNumericStatus() {
		return $this->numericStatus;
	}

	/**
	 * Get scan status as string
	 *
	 * @return string
	 */
	public function getDetails() {
		return $this->details;
	}

	/**
	 * @param string $rawResponse
	 * @param integer $result
	 */
	public function parseResponse($rawResponse, $result = null) {
		$matches = [];
		/** @phan-suppress-next-line PhanDeprecatedFunction */
		$ruleMapper = new Db\RuleMapper(\OC::$server->getDb());
		if ($result === null) { // Daemon or socket mode
			try {
				$allRules = $this->getResponseRules();
			} catch (\Exception $e) {
				\OCP\Util::writeLog(
					'files_antivirus',
					__METHOD__.', exception: '.$e->getMessage(),
					\OCP\Util::ERROR
				);
				return;
			}

			$isMatched = false;
			foreach ($allRules as $rule) {
				if (\preg_match($rule->getMatch(), $rawResponse, $matches)) {
					$isMatched = true;
					$this->numericStatus = \intval($rule->getStatus());
					if (\intval($rule->getStatus()) === self::SCANRESULT_CLEAN) {
						$this->details = '';
					} else {
						$this->details = isset($matches[1]) ? $matches[1] : 'unknown';
					}
					break;
				}
			}

			if (!$isMatched) {
				$this->numericStatus = self::SCANRESULT_UNCHECKED;
				$this->details = 'No matching rules. Please check antivirus rules.';
			}
		} else { // Executable mode
			$scanStatus = $ruleMapper->findByResult($result);
			if (\is_array($scanStatus) && \count($scanStatus)) {
				$this->numericStatus = \intval($scanStatus[0]->getStatus());
				$this->details = $scanStatus[0]->getDescription();
			}

			switch ($this->numericStatus) {
				case self::SCANRESULT_INFECTED:
					$report = [];
					$rawResponse = \explode("\n", $rawResponse);

					foreach ($rawResponse as $line) {
						if (\preg_match('/.*: (.*) FOUND\s*$/', $line, $matches)) {
							$report[] = $matches[1];
						}
					}
					$this->details = \implode(', ', $report);

					break;
				case self::SCANRESULT_UNCHECKED:
					if (!$this->details) {
						$this->details = 'No matching rule for exit code ' .  $this->numericStatus .'. Please check antivirus rules configuration.' ;
					}
			}
		}
	}

	protected function getResponseRules() {
		$infectedRules = $this->ruleMapper->findAllMatchedByStatus(self::SCANRESULT_INFECTED);
		$uncheckedRules = $this->ruleMapper->findAllMatchedByStatus(self::SCANRESULT_UNCHECKED);
		$cleanRules = $this->ruleMapper->findAllMatchedByStatus(self::SCANRESULT_CLEAN);

		$infectedRules = $infectedRules ? $infectedRules : [];
		$uncheckedRules = $uncheckedRules ? $uncheckedRules : [];
		$cleanRules = $cleanRules ? $cleanRules : [];

		// order: clean, infected, try to guess error
		return \array_merge($cleanRules, $infectedRules, $uncheckedRules);
	}

	/**
	 * @throws QueryException
	 * @throws NotFoundException
	 */
	public function dispatch(Item $item, $isBackground = false): void {
		switch ($this->getNumericStatus()) {
			case self::SCANRESULT_UNCHECKED:
				$item->processUnchecked($this);
				break;
			case self::SCANRESULT_INFECTED:
				$item->processInfected($this, $isBackground);
				break;
			case self::SCANRESULT_CLEAN:
				$item->processClean($isBackground);
				break;
		}
	}
}
