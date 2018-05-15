<?php
/**
 * ownCloud - Files_antivirus
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Viktar Dubiniuk 2017-2018
 * @license AGPL-3.0
 */

namespace OCA\Files_Antivirus\Scanner;

/**
 * Class External
 *
 * @package OCA\Files_Antivirus\Scanner
 */
abstract class External extends AbstractScanner {
	/**
	 * Send an empty chunk to indicate the end of stream,
	 * read response and close the handle
	 */
	protected function shutdownScanner() {
		@\fwrite($this->getWriteHandle(), \pack('N', 0));
		$response = \fgets($this->getWriteHandle());
		$this->logger->debug(
			'Response :: ' . $response,
			['app' => 'files_antivirus']
		);
		@\fclose($this->getWriteHandle());

		$this->status->parseResponse($response);
	}

	/**
	 * Prepend a chunk sent to ClamAv with its length
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	protected function prepareChunk($data) {
		$chunkLength = \pack('N', \strlen($data));
		return $chunkLength . $data;
	}
}
