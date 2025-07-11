# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [Unreleased] - XXXXXX


## [1.2.3] - 2025-06-26

### Fixed

- [#554](https://github.com/owncloud/files_antivirus/issues/554) - Fix cron execution with too few arguments
- [#559](https://github.com/owncloud/files_antivirus/issues/559) - Verify ClamAV connection


## [1.2.2] - 2023-06-13

### Fixed

- [#502](https://github.com/owncloud/files_antivirus/issues/502) - McAfee Webgateway causes a 60 seconds delay on each upload
- [#534](https://github.com/owncloud/files_antivirus/issues/534) - Fix enterprise check for ICAP
- [#539](https://github.com/owncloud/files_antivirus/issues/539) - Hide config details about background scan if disabled
- [#540](https://github.com/owncloud/files_antivirus/issues/540) - Fix hostname input validation


## [1.2.1] - 2022-11-21

### Fixed

- [#525](https://github.com/owncloud/files_antivirus/pull/525) - PHP Syntax error when upgrading to files_antivirus 1.2.0 on PHP 7.3 #525
- Translation updates

## [1.2.0] - 2022-11-17

### Added

- [#517](https://github.com/owncloud/files_antivirus/pull/517) - Add setting to enable/disable background scanning

### Fixed

- [#518](https://github.com/owncloud/files_antivirus/pull/518) - In case of missconfiguration a non-tech message is presented
- [#516](https://github.com/owncloud/files_antivirus/pull/516) - Av mode names & fortinet fields
- [#514](https://github.com/owncloud/files_antivirus/pull/514) - Upload cannot be deleted but only denied + use same message in two places
- [#512](https://github.com/owncloud/files_antivirus/pull/512) - Fortinet scanner file name + major code cleanup
- [#496](https://github.com/owncloud/files_antivirus/pull/496) - Daemon and Executable mode are specific to ClamAV
- [#455](https://github.com/owncloud/files_antivirus/pull/455) - Doc link for executable and params point to the wrong doc


## [1.1.0] - 2022-07-26

### Fixed

- [#477](https://github.com/owncloud/files_antivirus/pull/477) - Advanced settings (list of rules) is not displayed
- [#473](https://github.com/owncloud/files_antivirus/pull/473) - ICAP Response Modification Mode is missing
- [#495](https://github.com/owncloud/files_antivirus/pull/495) - Fix wrong offset for the ICAP protocol

### Added

- [#488](https://github.com/owncloud/files_antivirus/pull/488) - Add ICAP Scanner for FortiSandbox
- [#489](https://github.com/owncloud/files_antivirus/pull/489) - Add ICAP Scanner for McAfee Web Gateway 10.x #489


## [1.0.0] - 2021-05-31

### Fixed

- Prevent upload virus file with new public WebDAV API - [#334](https://github.com/owncloud/files_antivirus/pull/334)
- fix: handle McAfee response [#413](https://github.com/owncloud/files_antivirus/pull/413)
- docs: fix icap setup - [#417](https://github.com/owncloud/files_antivirus/pull/417)
- Improve validation pattern to check whether port number is in [1, 65535] range [423](https://github.com/owncloud/files_antivirus/pull/423)
- Prevent from crashing on missing or expired license [#426](https://github.com/owncloud/files_antivirus/pull/426)
- fix: [ICAP] Stop reading the response after headers are read - [#445](https://github.com/owncloud/files_antivirus/pull/445)

### Changed

- Prefer daemon or socket to executable mode if any of those is available [#399](https://github.com/owncloud/files_antivirus/pull/399)
- Do not depend on the sockets PHP extension [#428](https://github.com/owncloud/files_antivirus/pull/428)
- Move executable options into config.php [#442](https://github.com/owncloud/files_antivirus/pull/442)


## [0.16.0] - 2021-02-01

### Added

- Support for external scanner classes for e.g. ICAP integration - [#379](https://github.com/owncloud/files_antivirus/pull/379)

### Changed

- Owncloud 10.3+ required


## [0.15.2] - 2020-07-27

### Fixed

- Delete file infected directly on the physical storage on objectstorage.

## [0.15.1] - 2019-06-24

### Fixed

- correct logging of actions performed by cron job - [#306](https://github.com/owncloud/files_antivirus/issues/306)


## [0.15.0] - 2019-03-14

### Added

- Add a message to background job to help debugging issues - [#260](https://github.com/owncloud/files_antivirus/issues/260)

### Fixed

- Do not scan files if etag hasn't changed - [#288](https://github.com/owncloud/files_antivirus/issues/288)

## [0.14.0] - 2018-11-30

### Added

- Support for PHP 7.2 - [#256](https://github.com/owncloud/files_antivirus/issues/256)

### Changed

- Set max version to 10 because core platform is switching to Semver

## [0.13.0] - 2018-07-11
### Fixed

- Obey file size limits when uploads are chunked [#226](https://github.com/owncloud/files_antivirus/pull/226)
- Don't log exceptions on virus detection [#219](https://github.com/owncloud/files_antivirus/pull/219)

### Changed
- Return HTTP status code `403` on virus detection [#219](https://github.com/owncloud/files_antivirus/pull/219)

## [0.12.0] - 2018-02-08

### Added

 - Ability to disable background scan [213](https://github.com/owncloud/files_antivirus/pull/213)
 - A connection test after saving the settings. Notify admin if this test is failed [195](https://github.com/owncloud/files_antivirus/pull/195)
 - Scanning content in file_put_contents invocation [198](https://github.com/owncloud/files_antivirus/pull/198)

### Changed

 - Ignore calls to fopen in case there is no upload (scan file from the storage 
 wrapper only if it is related to the upload) [196](https://github.com/owncloud/files_antivirus/pull/196)
 - When antivirus is unreachable uploads are rejected [195](https://github.com/owncloud/files_antivirus/pull/195)

### Fixed

 - Proper validation/detection of inputs fields [212](https://github.com/owncloud/files_antivirus/pull/212)
 - Scanning when using public shared links [211](https://github.com/owncloud/files_antivirus/pull/211)
 - Improper size detection for chunking upload [196](https://github.com/owncloud/files_antivirus/pull/196)
 - Don't scan chunks for DAV v1/v2 [196](https://github.com/owncloud/files_antivirus/pull/196)

## [0.11.2] - 2017-09-28

### Added

 - Frontend Validation for config fields [187](https://github.com/owncloud/files_antivirus/pull/187)

## [0.11.1.0] - Unreleased

### Changed

- App description and makefile updated for new marketplace [161](https://github.com/owncloud/files_antivirus/pull/161)

### Fixed

- Oracle: Error when saving a rule  [167](https://github.com/owncloud/files_antivirus/pull/167)

## [0.10.1.0] - 2017-09-15

### Changed 

- DB schema ported from xml to migrations [169](https://github.com/owncloud/files_antivirus/pull/169)
- Do not scan individual chunks for chunked upload [175](https://github.com/owncloud/files_antivirus/pull/175)
- ownCloud 10.0.3+ required


## [0.10.0.2] - Unreleased

### Changed 

- fileid is changed to bigint [165](https://github.com/owncloud/files_antivirus/pull/165)

## [0.10.0.1] - 2017-07-04

### Fixed

- BGscanner query fix [159](https://github.com/owncloud/files_antivirus/pull/159)

## [0.10.0] - 2016-10-10

### Changed 

- Optimized query in a BG scanner [139](https://github.com/owncloud/files_antivirus/pull/139)
- ownCloud 10.0 required

### Fixed

- Always log a warning on uploading infected [132](https://github.com/owncloud/files_antivirus/issues/132)

## [0.9.0.1] - Unreleased

### Changed

- Backport Optimized query in a BG scanner  [174](https://github.com/owncloud/files_antivirus/pull/174)

### Fixed

- Fix Call to a member function getUser() on a non-object at stable9.1 [#156](https://github.com/owncloud/files_antivirus/pull/156/)

## [0.9.0.0] - 2016-03-23

### Changed

- TimedJob is used instead of legacy cron API [100](https://github.com/owncloud/files_antivirus/pull/100)

### Fixed

- Rule is duplicated on edit [111](https://github.com/owncloud/files_antivirus/pull/111)

## [0.8.1.0] - 2016-12-22

### Added

- Add huge files support by scanning them as chunks of size avStreamMaxLength [133](https://github.com/owncloud/files_antivirus/pull/133)

### Changed

- Background scanner scans 10 files per iteration now
- Saving of rules in advanced section

## [0.8.0.1] - 2016-01-31

### Fixed

- AntiVirus 0.7.0.1 crashes cron in OC 8.1 [63](https://github.com/owncloud/files_antivirus/issues/63)
- Change recipient name in notification mail if using user_ldap [66](https://github.com/owncloud/files_antivirus/issues/66)
- Infected file is moved only to trash if "delete file" is activated [68](https://github.com/owncloud/files_antivirus/issues/68)

## [0.8.0] - 2016-01-31

### Changed

- ownCloud 8.2 required

## [0.7.0.2] - 2016-01-31

### Changed

- Skip zero-sized files in background scanner 

## [0.7.0.1] - 2015-07-07

### Changed

- Shipped removed from appinfo
- ownCloud 8.1 required

## [0.7.0] - 2015-07-07

### Added

- Integration with Activity app [37](https://github.com/owncloud/files_antivirus/pull/37)

### Changed

- Refactored to use AppFramework controllers, DB Entities and Mappers 
- Log owner and path for infected files [13](https://github.com/owncloud/files_antivirus/issues/13)
- ownCloud 8.0 required

### Fixed

- Upgrade for sqlite [#6](https://github.com/owncloud/files_antivirus/pull/6)
- If the screen width is not very wide the buttons "Reset to default" and "Clear All" overlap the text. [#23](https://github.com/owncloud/files_antivirus/issues/23)
- Use storage wrapper instead of FS hooks. Fixes [15](https://github.com/owncloud/files_antivirus/issues/15)
- Some issues found by code checker [39](https://github.com/owncloud/files_antivirus/pull/39)
- Debug message missing in executable mode [#44](https://github.com/owncloud/files_antivirus/issues/44)

## [0.6.1] - 2014-11-23

### Added

- App icon [#3](https://github.com/owncloud/files_antivirus/pull/3)
- Manage antivirus statuses from admin
- Extra command line parameters in executable mode
- Routes

### Fixed

- Removed old non-existing background job
- Do not send email to guest users
- Fixed public upload
- Do not execute background job when app is disabled
- Renamed table files_antivirus_status into files_avir_status: key name was too long for Oracle
- Fixed saving rules for Oracle [#1](https://github.com/owncloud/files_antivirus/pull/1)

## [0.6.0] - 2014-04-03

### Added

- Unit tests
- Home storage class support

### Changed

- Do not scan directories and empty files
- Fileid is a primary key


## [0.5.0] - 2014-02-17

### Added

- Namespaces

### Changed

- Updated to use public API
- Socket mode refactored
- Use view to stream file contents to clamav
- Use storage to unlink infected file
- ownCloud 6 required
- Background job scanner updated

### Fixed

- Uploading a file to a read-write shared dir
- Error message in executable mode
- Outdated settings layout

## [0.4.1] - 2013-06-06

### Added

- ClamAV socket mode support

### Changed

- Use displayname in antivirus email
- Loglevel for ClamAV response decreased to debug

## [0.4.0] - 2013-04-09

### Changed

- Updated to new Filesystem API
- Updated to OCP mail functions
- ownCloud 5 required

### Fixed

- Admin check for settings
- Echo replaced with p

## [0.3.0] - 2013-01-18

### Added

- Background scanner
- Configurable action for infected files

## [0.2.0] - 2012-10-17

### Added

- Added onscreen notification for infected files
- Added email notification for infected files

### Fixed

- ClamAV executable mode

## [0.1.0] - 2012-09-19

### Added

- Initial implementation


[Unreleased]: https://github.com/owncloud/files_antivirus/compare/v1.2.2...master
[1.2.2]: https://github.com/owncloud/files_antivirus/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/owncloud/files_antivirus/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/owncloud/files_antivirus/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/owncloud/files_antivirus/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/owncloud/files_antivirus/compare/v0.16.0...v1.0.0
[0.16.0]: https://github.com/owncloud/files_antivirus/compare/v0.15.2...v0.16.0
[0.15.2]: https://github.com/owncloud/files_antivirus/compare/v0.15.1...v0.15.2
[0.15.1]: https://github.com/owncloud/files_antivirus/compare/v0.15.0...v0.15.1
[0.15.0]: https://github.com/owncloud/files_antivirus/compare/v0.14.0...v0.15.0
[0.14.0]: https://github.com/owncloud/files_antivirus/compare/v0.13.0...v0.14.0
[0.13.0]: https://github.com/owncloud/files_antivirus/compare/v0.12.0...v0.13.0
[0.12.0]: https://github.com/owncloud/files_antivirus/compare/v0.11.2...v0.12.0
