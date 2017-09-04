# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.11.1.0] - Unreleased

### Changed

- App description and makefile updated for new marketplace [161](https://github.com/owncloud/files_antivirus/pull/161)

### Fixed
- Oracle: Error when saving a rule  [167](https://github.com/owncloud/files_antivirus/pull/167)

## [0.10.1.0] - Unreleased

### Changed 

- DB schema ported from xml to migrations [169](https://github.com/owncloud/files_antivirus/pull/169)
- Do not scan individual chunks for chunked upload [175](upload https://github.com/owncloud/files_antivirus/pull/175)
- ownCloud 10.0.3+ required


## [0.10.0.2] - Unreleased

### Changed 

- fileid is changed to bigint [165](https://github.com/owncloud/files_antivirus/pull/165)

## [0.10.0.1] - 2017-07-04

### Fixed

- BGscanner query fix [159](https://github.com/owncloud/files_antivirus/pull/159)

## [0.10.0] - 2016-10-10

### Changed 

- Optimized query in a DB scanner [139](https://github.com/owncloud/files_antivirus/pull/139)
- ownCloud 10.0 required

### Fixed

- Always log a warning on uploading infected [132](file://github.com/owncloud/files_antivirus/issues/132)

## [0.9.0.1] - Unreleased

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

### Fixed

## [0.7.0.2] - 2016-01-31

### Fixed

- Skip zero-sized files in background scanner 

### Changed

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
- Do not send email to gues users
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
