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
* Go to Admin Panel and [configure](https://doc.owncloud.com/server/next/admin_manual/configuration/server/virus-scanner-support.html) the App

## Enterprise Feature: ICAP Antivirus integration

The Files Antivirus app can support the [ICAP](https://tools.ietf.org/html/rfc3507) protocol if you are using the ownCloud Enterprise Edition.

Using the ICAP mode requires a valid enterprise license. If no license key is present, it will trigger the grace period to obtain a valid key.
After the expiration of the grace period / license key, the files_antivirus app will be disabled.

### Run with c-icap/clamav

c-icap has a built-in clamav module see https://sourceforge.net/p/c-icap/wiki/ModulesConfiguration/

An out-of-the-box docker image  _for testing purpose_ is available at https://hub.docker.com/r/deepdiver/icap-clamav-service

For simple local testing run docker run -ti deepdiver/icap-clamav-service and get it's ip using docker inspect.
The IP address needs to be setup in the configuration - see above

The request service for clamav has to be set to 'avscan' and the response header to 'X-Infection-Found'


### Run with Kaspersky

Kaspersky provides docker images as well (https://box.kaspersky.com/d/c8d8577dc2494256b45e/)
Follow the instructions in Kaspersky ScanEngine for Kubernetes.7z

Additional configuration: 
Enable Allow204 - this is necessary to tell kav to not send back the file contents.
see https://support.kaspersky.com/ScanEngine/1.0/en-US/201151.htm

The request service for clamav has to be set to 'req' and the response header to 'X-Virus-ID'


NOTE: The older versions of KAV did not send back the virus/infection name in an icap header.

In v2.0.0 the header to transport the virus can be configured. Default: No header is sent.
see https://support.kaspersky.com/ScanEngine/1.0/en-US/201214.htm


### Run with FortiSandbox in ICAP Mode

Select 'Fortinet' from the dropdown.

The request service for FortiSandbox has to be set to 'respmod' and the response header to 'X-Virus-Name'.

Fortinet provides product trials of FortiSandbox, please have a look at [Fortinet](https://www.fortinet.com/de/products/sandbox/fortisandbox).


### Run with McAfee Web Gateway 10.x and higher in ICAP Mode

Select 'McAfee Web Gateway 10.x and higher' from the dropdown.

The request service for McAfee has to be set to 'respmod' and the response header to 'X-Virus-Name'.

McAfee provides product trial for evaluation purposes. Have a look at [the McAfee Webpage](https://www.skyhighsecurity.com/en-us/products/secure-web-gateway.html) for the Web Gateway.

Note: Product is now called 'Skyhigh Secure Web Gateway'

Authors:

[Manuel Delgado López](https://github.com/valarauco/) :: manuel.delgado at ucr.ac.cr  
[Bart Visscher](https://github.com/bartv2/)  
[Viktar Dubiniuk](https://github.com/vicdeo/)
