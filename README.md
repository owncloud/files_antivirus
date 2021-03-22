# ownCloud Antivirus App   

files_antivirus is an antivirus app for [ownCloud](https://github.com/owncloud) based on [ClamAV](http://www.clamav.net).

## Details

The idea is to check for virus at upload-time, notifying the user (on screen and/or email) and
remove the file if it's infected.

## QA metrics on master branch:

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/files_antivirus/status.svg?branch=master)](https://drone.owncloud.com/owncloud/files_antivirus)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_antivirus&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_files_antivirus)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_antivirus&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_files_antivirus)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_antivirus&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_files_antivirus)

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

## Enterprise Feature: ICAP Antivirus integration

The Files Antivirus app can support the [ICAP](https://tools.ietf.org/html/rfc3507) protocol if you are using the ownCloud Enterprise Edition.

### General configuration
```
  // mandatory - enables icap in files_antivirus
  'files-antivirus.scanner-class' => 'OCA\\ICAP\\Scanner',
  // mandatory - configures the icap server
  'files-antivirus.icap.host' => 'ip-or-hostname',
  // mandatory - defines the icap service which provides av scanning - product specific
  'files-antivirus.icap.req-service' => ''
  // optional - default is 1344 - port of the icap service
  'files-antivirus.icap.port' => '1344',
  // optional - default is 100MB - maximum file content which is transmitted to the icap server
  'files-antivirus.icap.max-transmission' => 4*1024*1024*1024,
  // optional - default X-Virus-ID - response header name which holds the virus information
  'files-antivirus.icap.response-header' => 'X-Infection-Found'
```

Setting the 'files-antivirus.scanner-class' config to 'OCA\\ICAP\\Scanner' requires a valid enterprise license. If no license key is present, it will trigger the grace period to obtain a valid key.
After the expiration of the grace period / license key, the files_antivirus app will be disabled.

### Run with c-icap/clamav

c-icap has a built in clamav module see https://sourceforge.net/p/c-icap/wiki/ModulesConfiguration/

An out of the box docker image is available at https://hub.docker.com/r/deepdiver/icap-clamav-service

For simple local testing run docker run -ti deepdiver/icap-clamav-service and get it's ip using docker inspect.
The IP address needs to be setup in the configuration - see above
```
  'files-antivirus.icap.req-service' => 'avscan',
  'files-antivirus.icap.response-header' => 'X-Infection-Found'
```

### Run with Kaspersky

Kaspersky provides docker images as well (https://box.kaspersky.com/d/c8d8577dc2494256b45e/)
Follow the instructions in Kaspersky ScanEngine for Kubernetes.7z

Additional configuration: Set <Configuration><ICAPSettings><Allow204>1</Allow204></ICAPSettings></Configuration> in kavicapd.xml

Specific configuration to KAV:
```
  'files-antivirus.icap.req-service' => 'req',
  // header has to match the KAV configuration - available with KAV in early 2021
  'files-antivirus.icap.response-header' => 'X-Virus-ID'
```

NOTE: The older versions of KAV did not sending back the virus/infection name in an icap header.

In v2.0.0 the header to transport the virus can be configured. Default: No header is sent.
```
sed -i -e 's@<VirusNameICAPHeader.*@<VirusNameICAPHeader>X-Infection-Found</VirusNameICAPHeader> <SentVirusNameICAPHeader>X-Infection-Found</SentVirusNameICAPHeader>@' /opt/kaspersky/ScanEngine/etc/kavicapd.xml
/opt/kaspersky/ScanEngine/etc/init.d/kavicapd restart
```

Authors:

[Manuel Delgado López](https://github.com/valarauco/) :: manuel.delgado at ucr.ac.cr  
[Bart Visscher](https://github.com/bartv2/)  
[Viktar Dubiniuk](https://github.com/vicdeo/)
