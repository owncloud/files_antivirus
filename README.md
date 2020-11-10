# ownCloud Antivirus App   

files_antivirus is an antivirus app for [ownCloud](https://github.com/owncloud) based on [ClamAV](http://www.clamav.net).

Trigger CI

## Details

The idea is to check for virus at upload-time, notifying the user (on screen and/or email) and
remove the file if it's infected.

## QA metrics on master branch:

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/files_antivirus/status.svg?branch=master)](https://drone.owncloud.com/owncloud/files_antivirus)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/owncloud/files_antivirus/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/owncloud/files_antivirus/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/owncloud/files_antivirus/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/owncloud/files_antivirus/?branch=master)


## Status

The App is not complete yet, the following works/is done:
* It can be configured to work with the executable or the daemon mode of ClamAV
* If used in daemon mode it can connect through network- or local file-socket
* In daemon mode, it sends files to a remote/local server using INSTREAM command
* When the user uploads a file, it's checked
* If an uploaded file is infected, it's deleted and a notification is shown to the user on screen and an email is sent with details.
* Tested in Linux only
* Background Job to scan all files
* Test uploading from clients
* File size limit

## ToDo


* Configurations Tuneups
* Other OS Testing
* Look for ideas :P

## Requirements

* ClamAV (Binaries or a server running ClamAV in daemon mode)


## Install

* Install and enable the App
* Go to Admin Panel and [configure](https://doc.owncloud.org/server/10.0/admin_manual/configuration/server/antivirus_configuration.html) the App


Authors:

[Manuel Delgado López](https://github.com/valarauco/) :: manuel.delgado at ucr.ac.cr  
[Bart Visscher](https://github.com/bartv2/)  
[Viktar Dubiniuk](https://github.com/vicdeo/)
