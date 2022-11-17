<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<p><?php p(\str_replace('{user}', $_['user'], $l->t('Greetings {user},'))); ?> </p>
<p style='margin-left:20px'><?php p($l->t('A malware or virus was detected, your upload was denied. In doubt or for details please contact your system administrator.')); ?> <br />
   <?php p(\str_replace('{host}', $_['host'], $l->t('This email is a notification from {host}. Please, do not reply.'))); ?> </p>
<p style='margin-left:20px'><?php p(\str_replace('{file}', $_['file'], $l->t('File uploaded: {file}'))); ?> </p>
