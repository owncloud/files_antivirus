@api
Feature: Antivirus basic
  As a system administrator and user
  In order to protect myself and others from known viruses
  The system should prevent files with viruses from being uploaded

  Background:
    Given the administrator has enabled the files_antivirus app
    And the owncloud log level has been set to warning
    And the owncloud log has been cleared
    And user "Alice" has been created with default attributes and small skeleton files

  Scenario Outline: A small file without a virus can be uploaded
    Given using <dav-path-version> DAV path
    When user "Alice" uploads file "textfile.txt" from the antivirus test data folder to "/ok-textfile.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And as "Alice" file "/ok-textfile.txt" should exist
    And the content of file "/ok-textfile.txt" for user "Alice" should be "Small text file without virus."
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: A small file with a virus cannot be uploaded
    Given using <dav-path-version> DAV path
    When user "Alice" uploads file "eicar.com" from the antivirus test data folder to "/virusfile.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app             | method | message               |
      | Alice | files_antivirus | PUT    | Infected file deleted |
    And as "Alice" file "/virusfile.txt" should not exist
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: A small file with a virus can be uploaded when the antivirus app is disabled
    Given using <dav-path-version> DAV path
    When the administrator disables the files_antivirus app
    And user "Alice" uploads file "eicar.com" from the antivirus test data folder to "/virusfile.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And the log file should not contain any log-entries containing these attributes:
      | user  | app             | method | message               |
      | Alice | files_antivirus | PUT    | Infected file deleted |
    And as "Alice" file "/virusfile.txt" should exist
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: A small file without a virus can be uploaded in chunks
    Given using <dav-path-version> DAV path
    When user "Alice" uploads the following chunks to "/myChunkedFile.txt" with <dav-path-version> chunking and using the WebDAV API
      | number | content |
      | 1      | AAAAA   |
      | 2      | BBBBB   |
      | 3      | CCCCC   |
    Then the HTTP status code should be "201"
    And as "Alice" file "/myChunkedFile.txt" should exist
    And the content of file "/myChunkedFile.txt" for user "Alice" should be "AAAAABBBBBCCCCC"
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: A small file with a virus cannot be uploaded in chunks
    Given using <dav-path-version> DAV path
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

  Scenario: A small file with a virus cannot be uploaded in chunks (use async move to upload)
    Given using new DAV path
    And the administrator has enabled async operations
    And user "Alice" has created a new chunking upload with id "chunking-42"
    And user "Alice" has uploaded new chunk file "1" with "X5O!P%@AP[4\PZX54(P^)7C" to id "chunking-42"
    And user "Alice" has uploaded new chunk file "2" with "C)7}$EICAR-STANDARD-ANT" to id "chunking-42"
    And user "Alice" has uploaded new chunk file "3" with "IVIRUS-TEST-FILE!$H+H*" to id "chunking-42"
    When user "Alice" moves new chunk file with id "chunking-42" asynchronously to "/myChunkedFile.txt" using the WebDAV API
    Then the HTTP status code should be "202"
    And the oc job status values of last request for user "Alice" should match these regular expressions
      | status | /^error$/ |
    And as "Alice" file "/myChunkedFile.txt" should not exist

  Scenario Outline: A small file without a virus can be uploaded via public upload
    Given the administrator has enabled DAV tech_preview
    And as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    When the public uploads file "textfile.txt" from the antivirus test data folder using the <public-webdav-api> WebDAV API
    Then the HTTP status code should be "201"
    And as "Alice" file "/FOLDER/textfile.txt" should exist
    Examples:
      | public-webdav-api |
      | new               |
      | old               |

  Scenario Outline: A small file with a virus cannot be uploaded via old public upload
    Given the administrator has enabled DAV tech_preview
    And as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    When the public uploads file "<virus-file-name>" from the antivirus test data folder using the old WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And as "Alice" file "/FOLDER/<virus-file-name>" should not exist
    Examples:
      | virus-file-name |
      | eicar.com       |
      | eicar_com.zip   |
      | eicarcom2.zip   |

  Scenario Outline: A small file with a virus cannot be uploaded via new public upload
    Given as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    When the public uploads file "<virus-file-name>" from the antivirus test data folder using the new WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And as "Alice" file "/FOLDER/<virus-file-name>" should not exist
    Examples:
      | virus-file-name |
      | eicar.com       |
      | eicar_com.zip   |
      | eicarcom2.zip   |

  @skip @files_primary_s3-issue-100
  Scenario: A file cannot be overwritten with a file containing a virus via public upload
    Given as user "Alice"
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    When the public uploads file "textfile.txt" from the antivirus test data folder using the old WebDAV API
    And the public overwrites file "textfile.txt" with content "X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*" using the old WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And the content of file "/FOLDER/textfile.txt" for user "Alice" should be "Small text file without virus."

  Scenario Outline: An empty file can be uploaded
    Given using <dav-path-version> DAV path
    When user "Alice" uploads file with content "" to "empty-file.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And as "Alice" file "/empty-file.txt" should exist
    And the content of file "/empty-file.txt" for user "Alice" should be ""
    Examples:
      | dav-path-version |
      | old              |
      | new              |


  Scenario: A file cannot be overwritten with a file containing a virus via public upload
    Given the administrator has enabled DAV tech_preview
    And user "Alice" has created a public link share of folder "FOLDER" with change permissions
    When the public uploads file "textfile.txt" from the antivirus test data folder using the new WebDAV API
    And the public overwrites file "textfile.txt" with content "X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*" using the new WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user | app             | method | message               |
      | --   | files_antivirus | PUT    | Infected file deleted |
    And the content of file "/FOLDER/textfile.txt" for user "Alice" should be "Small text file without virus."

  @skip @files_primary_s3-issue-100
  Scenario Outline: overwriting a file with virus is not possible
    Given using <dav-path-version> DAV path
    And user "Alice" has uploaded file "textfile.txt" from the antivirus test data folder to "/ok-textfile.txt"
    When user "Alice" uploads file "eicar.com" from the antivirus test data folder to "/ok-textfile.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app             | method | message               |
      | Alice | files_antivirus | PUT    | Infected file deleted |
    And the content of file "/ok-textfile.txt" for user "Alice" should be "Small text file without virus."
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  @skip @files_primary_s3-issue-100
  Scenario Outline: overwriting a file with virus in a group share is not possible
    Given using <dav-path-version> DAV path
    And user "Brian" has been created with default attributes and without skeleton files
    And group "grp1" has been created
    And user "Brian" has been added to group "grp1"
    And user "Alice" has been added to group "grp1"
    And user "Alice" has uploaded file "textfile.txt" from the antivirus test data folder to "/ok-textfile.txt"
    And user "Alice" has shared file "/ok-textfile.txt" with group "grp1"
    And user "Brian" has accepted share "/ok-textfile.txt" offered by user "Alice"
    When user "Brian" uploads file "eicar.com" from the antivirus test data folder to "/ok-textfile.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app             | method | message               |
      | Brian | files_antivirus | PUT    | Infected file deleted |
    And the content of file "/ok-textfile.txt" for user "Alice" should be "Small text file without virus."
    And the content of file "/ok-textfile.txt" for user "Brian" should be "Small text file without virus."
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  @skip @files_primary_s3-issue-100
  Scenario Outline: overwriting a file with virus in a share is not possible
    Given using <dav-path-version> DAV path
    And user "Brian" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file "textfile.txt" from the antivirus test data folder to "/ok-textfile.txt"
    And user "Alice" has shared file "/ok-textfile.txt" with user "Brian"
    And user "Brian" has accepted share "/ok-textfile.txt" offered by user "Alice"
    When user "Brian" uploads file "eicar.com" from the antivirus test data folder to "/ok-textfile.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And the last lines of the log file should contain log-entries containing these attributes:
      | user  | app             | method | message               |
      | Brian | files_antivirus | PUT    | Infected file deleted |
    And the content of file "/ok-textfile.txt" for user "Alice" should be "Small text file without virus."
    And the content of file "/ok-textfile.txt" for user "Brian" should be "Small text file without virus."
    Examples:
      | dav-path-version |
      | old              |
      | new              |
