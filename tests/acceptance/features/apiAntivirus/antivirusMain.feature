@api
Feature: Antivirus basic
	As a system administrator and user
	In order to protect myself and others from known viruses
	The system should prevent files with viruses from being uploaded

	Background:
		Given the administrator has enabled the files_antivirus app
		And the owncloud log level has been set to warning
		And the owncloud log has been cleared
		And user "user0" has been created

	Scenario: A small file without a virus can be uploaded
		When user "user0" uploads file "data/textfile.txt" to "/ok-textfile.txt" using the API
		Then the HTTP status code should be "201"
		And as "user0" the file "/ok-textfile.txt" should exist
		And the content of file "/ok-textfile.txt" for user "user0" should be "Small text file without virus."

	Scenario: A small file with a virus cannot be uploaded
		When user "user0" uploads file "data/eicar.com" to "/virusfile.txt" using the API
		Then the HTTP status code should be "403"
		And the last lines of the log file should contain log-entries containing these attributes:
			| user  | app             | method | message               |
			| user0 | files_antivirus | PUT    | Infected file deleted |
		And as "user0" the file "/virusfile.txt" should not exist

	Scenario: A small file with a virus can be uploaded when the antivirus app is disabled
		When the administrator disables the files_antivirus app
		And user "user0" uploads file "data/eicar.com" to "/virusfile.txt" using the API
		Then the HTTP status code should be "201"
		And the log file should not contain any log-entries containing these attributes:
			| user  | app             | method | message               |
			| user0 | files_antivirus | PUT    | Infected file deleted |
		And as "user0" the file "/virusfile.txt" should exist

	Scenario: A small file without a virus can be uploaded in chunks using API version 1 and old DAV path
		Given using API version "1"
		And using old DAV path
		When user "user0" uploads the following "3" chunks to "/myChunkedFile.txt" with old chunking and using the API
			| 1 | AAAAA |
			| 2 | BBBBB |
			| 3 | CCCCC |
		Then the HTTP status code should be "200"
		And as "user0" the file "/myChunkedFile.txt" should exist
		And the content of file "/myChunkedFile.txt" for user "user0" should be "AAAAABBBBBCCCCC"

	Scenario: A small file with a virus cannot be uploaded in chunks using API version 1 and old DAV path
		Given using API version "1"
		And using old DAV path
		When user "user0" uploads the following "3" chunks to "/myChunkedFile.txt" with old chunking and using the API
			| 1 | X5O!P%@AP[4\PZX54(P^)7C |
			| 2 | C)7}$EICAR-STANDARD-ANT |
			| 3 | IVIRUS-TEST-FILE!$H+H*  |
		Then the HTTP status code should be "403"
		And the last lines of the log file should contain log-entries containing these attributes:
			| user  | app             | method | message               |
			| user0 | files_antivirus | PUT    | Infected file deleted |
		And as "user0" the file "/myChunkedFile.txt" should not exist

	Scenario Outline: A small file without a virus can be uploaded in chunks using either API version and new DAV path
		Given using API version "<api-version>"
		And using new DAV path
		When user "user0" uploads the following chunks to "/myChunkedFile.txt" with new chunking and using the API
			| 1 | AAAAA |
			| 2 | BBBBB |
			| 3 | CCCCC |
		Then the HTTP status code should be "201"
		And as "user0" the file "/myChunkedFile.txt" should exist
		And the content of file "/myChunkedFile.txt" for user "user0" should be "AAAAABBBBBCCCCC"
		Examples:
			| api-version |
			| 1           |
			| 2           |

	Scenario Outline: A small file with a virus cannot be uploaded in chunks using either API version and new DAV path
		Given using API version "<api-version>"
		And using new DAV path
		When user "user0" uploads the following chunks to "/myChunkedFile.txt" with new chunking and using the API
			| 1 | X5O!P%@AP[4\PZX54(P^)7C |
			| 2 | C)7}$EICAR-STANDARD-ANT |
			| 3 | IVIRUS-TEST-FILE!$H+H*  |
		Then the HTTP status code should be "403"
		And the last lines of the log file should contain log-entries containing these attributes:
			| user  | app             | method | message               |
			| user0 | files_antivirus | MOVE   | Infected file deleted |
		And as "user0" the file "/myChunkedFile.txt" should not exist
		Examples:
			| api-version |
			| 1           |
			| 2           |
