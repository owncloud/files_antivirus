<?php

namespace OCA\Files_Antivirus;

use OCP\IL10N;

class L10n {
	public static function getEnduserNotification(IL10N $n) {
		return $n->t('Either the ownCloud antivirus app is misconfigured or the external antivirus service is not accessible. Please reach out to your system administrator!');
	}
}
