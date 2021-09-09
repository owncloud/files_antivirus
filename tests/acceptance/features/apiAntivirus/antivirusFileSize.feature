@api
Feature: Antivirus file size
  As a system administrator
  I want to be able to set the maximum file size for scanned files
  So that the system will not be overloaded

  Background:
    Given the administrator has enabled the files_antivirus app
    And the owncloud log level has been set to warning
    And the owncloud log has been cleared
    And user "Alice" has been created with default attributes and small skeleton files

  Scenario: Files smaller than the upload threshold are checked for viruses
    Given parameter "av_max_file_size" of app "files_antivirus" has been set to "100"
    When user "Alice" uploads file "eicar.com" from the antivirus test data folder to "/virusfile.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app             | method | message               |
      | Alice | files_antivirus | PUT    | Infected file deleted |
    And as "Alice" file "/virusfile.txt" should not exist

  Scenario: Files bigger than the upload threshold are not checked for viruses
    Given parameter "av_max_file_size" of app "files_antivirus" has been set to "100"
    When user "Alice" uploads file "eicar_com.zip" from the antivirus test data folder to "/virusfile.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And as "Alice" file "/virusfile.txt" should exist

  Scenario Outline: Files smaller than the upload threshold are checked for viruses when using chunking
    Given parameter "av_max_file_size" of app "files_antivirus" has been set to "100"
    And using <dav-path-version> DAV path
    When user "Alice" uploads the following chunks to "/myChunkedFile.txt" with <dav-path-version> chunking and using the WebDAV API
      | number | content                 |
      | 1      | X5O!P%@AP[4\PZX54(P^)7C |
      | 2      | C)7}$EICAR-STANDARD-ANT |
      | 3      | IVIRUS-TEST-FILE!$H+H*  |
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app             | message               |
      | Alice | files_antivirus | Infected file deleted |
    And as "Alice" file "/myChunkedFile.txt" should not exist
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario: Files smaller than the upload threshold are checked for viruses when using chunking (use async move to upload)
    Given parameter "av_max_file_size" of app "files_antivirus" has been set to "100"
    And using new DAV path
    And the administrator has enabled async operations
    When user "Alice" uploads the following chunks asynchronously to "/myChunkedFile.txt" with new chunking and using the WebDAV API
      | number | content                 |
      | 1      | X5O!P%@AP[4\PZX54(P^)7C |
      | 2      | C)7}$EICAR-STANDARD-ANT |
      | 3      | IVIRUS-TEST-FILE!$H+H*  |
    Then the HTTP status code should be "202"
    And the oc job status values of last request for user "Alice" should match these regular expressions
      | status | /^error$/ |
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app               | message               |
      | Alice | files_antivirus   | Infected file deleted |
      | Alice | no app in context | Exception             |
      | Alice | no app in context | Exception             |
    And as "Alice" file "/myChunkedFile.txt" should not exist

  Scenario Outline: Files bigger than the upload threshold are not checked for viruses when using chunking
    Given parameter "av_max_file_size" of app "files_antivirus" has been set to "20"
    And using <dav-path-version> DAV path
    When user "Alice" uploads the following chunks to "/myChunkedFile.txt" with <dav-path-version> chunking and using the WebDAV API
      | number | content                 |
      | 1      | X5O!P%@AP[4\PZX54(P^)7C |
      | 2      | C)7}$EICAR-STANDARD-ANT |
      | 3      | IVIRUS-TEST-FILE!$H+H*  |
    Then the HTTP status code should be "201"
    And as "Alice" file "/myChunkedFile.txt" should exist
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario: Files bigger than the upload threshold are not checked for viruses when using chunking (use async move to upload)
    Given parameter "av_max_file_size" of app "files_antivirus" has been set to "20"
    And using new DAV path
    And the administrator has enabled async operations
    When user "Alice" uploads the following chunks asynchronously to "/myChunkedFile.txt" with new chunking and using the WebDAV API
      | number | content                 |
      | 1      | X5O!P%@AP[4\PZX54(P^)7C |
      | 2      | C)7}$EICAR-STANDARD-ANT |
      | 3      | IVIRUS-TEST-FILE!$H+H*  |
    Then the HTTP status code should be "202"
    And the oc job status values of last request for user "Alice" should match these regular expressions
      | status | /^finished$/ |
    And as "Alice" file "/myChunkedFile.txt" should exist

  Scenario: Files smaller than the upload threshold are checked for viruses when uploaded via old public upload
    Given the administrator has enabled DAV tech_preview
    And as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    And parameter "av_max_file_size" of app "files_antivirus" has been set to "100"
    When the public uploads file "eicar.com" from the antivirus test data folder using the old WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And as "Alice" file "/FOLDER/eicar.com" should not exist

  Scenario: Files smaller than the upload threshold are checked for viruses when uploaded via new public upload
    Given as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    And parameter "av_max_file_size" of app "files_antivirus" has been set to "100"
    When the public uploads file "eicar.com" from the antivirus test data folder using the new WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And as "Alice" file "/FOLDER/eicar.com" should not exist

  Scenario Outline: Files bigger than the upload threshold are not checked for viruses when uploaded via public upload
    Given the administrator has enabled DAV tech_preview
    And as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    And parameter "av_max_file_size" of app "files_antivirus" has been set to "100"
    When the public uploads file "eicar_com.zip" from the antivirus test data folder using the <public-webdav-api> WebDAV API
    Then the HTTP status code should be "201"
    And as "Alice" file "/FOLDER/eicar_com.zip" should exist
    Examples:
      | public-webdav-api |
      | new               |
      | old               |

  @skip @files_primary_s3-issue-100
  Scenario: Files smaller than the upload threshold are checked for viruses when uploaded overwriting via public upload
    Given as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    And parameter "av_max_file_size" of app "files_antivirus" has been set to "100"
    When the public uploads file "textfile.txt" from the antivirus test data folder using the old WebDAV API
    And the public overwrites file "textfile.txt" with content "X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*" using the old WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And the content of file "/FOLDER/textfile.txt" for user "Alice" should be "Small text file without virus."

  Scenario Outline: Files bigger than the upload threshold are not checked for viruses when uploaded overwriting via public upload
    Given the administrator has enabled DAV tech_preview
    And as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    And parameter "av_max_file_size" of app "files_antivirus" has been set to "60"
    When the public uploads file "textfile.txt" from the antivirus test data folder using the <public-webdav-api> WebDAV API
    And the public overwrites file "textfile.txt" with content "X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*" using the old WebDAV API
    Then the HTTP status code should be "204"
    And the content of file "/FOLDER/textfile.txt" for user "Alice" should be "X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*"
    Examples:
      | public-webdav-api |
      | new               |
      | old               |
