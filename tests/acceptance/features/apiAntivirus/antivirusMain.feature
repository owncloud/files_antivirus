@api
Feature: Antivirus basic
  As a system administrator and user
  In order to protect myself and others from known viruses
  The system should prevent files with viruses from being uploaded

  Background:
    Given the administrator has enabled the files_antivirus app
    And the owncloud log level has been set to warning
    And the owncloud log has been cleared
    And user "user0" has been created with default attributes

  Scenario Outline: A small file without a virus can be uploaded
    Given using <dav-path-version> DAV path
    When user "user0" uploads file "textfile.txt" from the antivirus test data folder to "/ok-textfile.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And as "user0" file "/ok-textfile.txt" should exist
    And the content of file "/ok-textfile.txt" for user "user0" should be "Small text file without virus."
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: A small file with a virus cannot be uploaded
    Given using <dav-path-version> DAV path
    When user "user0" uploads file "eicar.com" from the antivirus test data folder to "/virusfile.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app             | method | message               |
      | user0 | files_antivirus | PUT    | Infected file deleted |
    And as "user0" file "/virusfile.txt" should not exist
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: A small file with a virus can be uploaded when the antivirus app is disabled
    Given using <dav-path-version> DAV path
    When the administrator disables the files_antivirus app
    And user "user0" uploads file "eicar.com" from the antivirus test data folder to "/virusfile.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And the log file should not contain any log-entries containing these attributes:
      | user  | app             | method | message               |
      | user0 | files_antivirus | PUT    | Infected file deleted |
    And as "user0" file "/virusfile.txt" should exist
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: A small file without a virus can be uploaded in chunks
    Given using <dav-path-version> DAV path
    When user "user0" uploads the following chunks to "/myChunkedFile.txt" with <dav-path-version> chunking and using the WebDAV API
      | 1 | AAAAA |
      | 2 | BBBBB |
      | 3 | CCCCC |
    Then the HTTP status code should be "201"
    And as "user0" file "/myChunkedFile.txt" should exist
    And the content of file "/myChunkedFile.txt" for user "user0" should be "AAAAABBBBBCCCCC"
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: A small file with a virus cannot be uploaded in chunks
    Given using <dav-path-version> DAV path
    When user "user0" uploads the following chunks to "/myChunkedFile.txt" with <dav-path-version> chunking and using the WebDAV API
      | 1 | X5O!P%@AP[4\PZX54(P^)7C |
      | 2 | C)7}$EICAR-STANDARD-ANT |
      | 3 | IVIRUS-TEST-FILE!$H+H*  |
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app             | message               |
      | user0 | files_antivirus | Infected file deleted |
    And as "user0" file "/myChunkedFile.txt" should not exist
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario: A small file with a virus cannot be uploaded in chunks (use async move to upload)
    Given using new DAV path
    And the administrator has enabled async operations
    And user "user0" has created a new chunking upload with id "chunking-42"
    And user "user0" has uploaded new chunk file "1" with "X5O!P%@AP[4\PZX54(P^)7C" to id "chunking-42"
    And user "user0" has uploaded new chunk file "2" with "C)7}$EICAR-STANDARD-ANT" to id "chunking-42"
    And user "user0" has uploaded new chunk file "3" with "IVIRUS-TEST-FILE!$H+H*" to id "chunking-42"
    When user "user0" moves new chunk file with id "chunking-42" asynchronously to "/myChunkedFile.txt" using the WebDAV API
    Then the HTTP status code should be "202"
    And the oc job status values of last request for user "user0" should match these regular expressions
      | status | /^error$/      |
    And as "user0" file "/myChunkedFile.txt" should not exist

  Scenario: A small file without a virus can be uploaded via public upload
    Given as user "user0"
    And user "user0" has created a public link share of folder "FOLDER" with change permissions
    When the public uploads file "textfile.txt" from the antivirus test data folder using the old WebDAV API
    Then the HTTP status code should be "201"
    And as "user0" file "/FOLDER/textfile.txt" should exist

  Scenario Outline: A small file with a virus cannot be uploaded via public upload
    Given as user "user0"
    And user "user0" has created a public link share of folder "FOLDER" with change permissions
    When the public uploads file "<virus-file-name>" from the antivirus test data folder using the old WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And as "user0" file "/FOLDER/<virus-file-name>" should not exist
    Examples:
      | virus-file-name |
      | eicar.com       |
      | eicar_com.zip   |
      | eicarcom2.zip   |

  @skip @files_primary_s3#69
  Scenario: A file cannot be overwritten with a file containing a virus via public upload
    Given as user "user0"
    And user "user0" has created a public link share of folder "FOLDER" with change permissions
    When the public uploads file "textfile.txt" from the antivirus test data folder using the old WebDAV API
    And the public overwrites file "textfile.txt" with content "X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*" using the old WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And the content of file "/FOLDER/textfile.txt" for user "user0" should be "Small text file without virus."
