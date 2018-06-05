@api
Feature: Antivirus basic
	As a system administrator and user
	In order to protect myself and others from known viruses
	The system should prevent files with viruses from being uploaded

	Background:
		Given the administrator has enabled the files_antivirus app
		And the owncloud log has been cleared
		And user "user0" has been created

	Scenario: A small file without a virus can be uploaded
		When user "user0" uploads file "data/textfile.txt" to "/ok-textfile.txt" using the API
		Then the HTTP status code should be "201"
		And as "user0" the file "/ok-textfile.txt" should exist
		And the content of file "/ok-textfile.txt" for user "user0" should be "Small text file without virus."

	Scenario: A small file with a virus cannot be uploaded
		When user "user0" uploads file "data/virusfile.txt" to "/virusfile.txt" using the API
		Then the HTTP status code should be "403"
		And the last lines of the log file should contain log-entries containing these attributes:
			| user  | app             | method | message               |
			| user0 | files_antivirus | PUT    | Infected file deleted |
		And as "user0" the file "/virusfile.txt" should not exist

	Scenario: A small file with a virus can be uploaded when the antivirus app is disabled
		When the administrator disables the files_antivirus app
		And user "user0" uploads file "data/virusfile.txt" to "/virusfile.txt" using the API
		Then the HTTP status code should be "201"
		And the log file should not contain any log-entries containing these attributes:
			| user  | app             | method | message               |
			| user0 | files_antivirus | PUT    | Infected file deleted |
		And as "user0" the file "/virusfile.txt" should exist

	Scenario: A small file without a virus can be uploaded in chunks using API version 1 and old DAV path
		Given using API version "1"
		And using old DAV path
		When user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChunkedFile.txt" using the API
		And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChunkedFile.txt" using the API
		And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChunkedFile.txt" using the API
		Then as "user0" the file "/myChunkedFile.txt" should exist
		And the content of file "/myChunkedFile.txt" for user "user0" should be "AAAAABBBBBCCCCC"

	Scenario: A small file with a virus cannot be uploaded in chunks using API version 1 and old DAV path
		Given using API version "1"
		And using old DAV path
		When user "user0" uploads chunk file "1" of "3" with "This kitten " to "/myChunkedFile.txt" using the API
		And user "user0" uploads chunk file "2" of "3" with "is a nasty " to "/myChunkedFile.txt" using the API
		And user "user0" uploads chunk file "3" of "3" with "virus" to "/myChunkedFile.txt" using the API
		Then the last lines of the log file should contain log-entries containing these attributes:
			| user  | app             | method | message               |
			| user0 | files_antivirus | PUT    | Infected file deleted |
		And as "user0" the file "/myChunkedFile.txt" should not exist

	Scenario Outline: A small file without a virus can be uploaded in chunks using API version 1 and new DAV path
		Given using API version "<api-version>"
		And using new DAV path
		When user "user0" creates a new chunking upload with id "chunking-42" using the API
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42" using the API
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42" using the API
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42" using the API
		And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt" with size 15 using the API
		Then as "user0" the file "/myChunkedFile.txt" should exist
		And the content of file "/myChunkedFile.txt" for user "user0" should be "AAAAABBBBBCCCCC"
		Examples:
			| api-version |
			| 1           |
			| 2           |

	Scenario Outline: A small file with a virus cannot be uploaded in chunks using API version 1 and new DAV path
		Given using API version "<api-version>"
		And using new DAV path
		When user "user0" creates a new chunking upload with id "chunking-42" using the API
		And user "user0" uploads new chunk file "1" with "This kitten " to id "chunking-42" using the API
		And user "user0" uploads new chunk file "2" with "is a nasty " to id "chunking-42" using the API
		And user "user0" uploads new chunk file "3" with "virus" to id "chunking-42" using the API
		And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt" with size 28 using the API
		Then the last lines of the log file should contain log-entries containing these attributes:
			| user  | app             | method | message               |
			| user0 | files_antivirus | MOVE   | Infected file deleted |
		And as "user0" the file "/myChunkedFile.txt" should not exist
		Examples:
			| api-version |
			| 1           |
			| 2           |
