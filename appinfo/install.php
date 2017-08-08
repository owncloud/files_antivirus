<?php

\OC::$server->getConfig()->setAppValue('files_antivirus', 'av_path', '/usr/bin/clamscan');
\OC::$server->getJobList()->add('OCA\Files_Antivirus\Cron\Task');
