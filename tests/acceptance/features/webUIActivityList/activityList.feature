@webUI @insulated @disablePreviews @activity-app-required
Feature: log activities of blocked files

  Background:
    Given these users have been created with skeleton files:
      | username |
      | user1    |
    And user "user1" has logged in using the webUI
    And the user has browsed to the files page

  Scenario Outline: uploading a virus file should be listed in the general activity list
    Given using <dav-path-version> DAV path
    And user "user1" has uploaded file "eicar.com" from the antivirus test data folder to "/new-file.txt"
    When the user browses to the activity page
    Then the activity number 1 should contain message "File files/new-file.txt" in the activity page
    And the activity number 1 should contain message "is infected with Eicar-Test-Signature" in the activity page
    When the user filters activity list by "Antivirus"
    Then the activity number 1 should contain message "File files/new-file.txt" in the activity page
    And the activity number 1 should contain message "is infected with Eicar-Test-Signature" in the activity page
    And the activity should not have any message with keyword "create"
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario Outline: replacing a file by a virus file should be listed in the general activity list
    Given using <dav-path-version> DAV path
    And user "user1" has uploaded file "eicar.com" from the antivirus test data folder to "lorem.txt"
    When the user browses to the activity page
    Then the activity number 1 should contain message "File files/lorem.txt" in the activity page
    And the activity number 1 should contain message "is infected with Eicar-Test-Signature" in the activity page
    And the activity should not have any message with keyword "change"
    When the user filters activity list by "Antivirus"
    Then the activity number 1 should contain message "File files/lorem.txt" in the activity page
    And the activity number 1 should contain message "is infected with Eicar-Test-Signature" in the activity page
    And the activity should not have any message with keyword "change"
    Examples:
      | dav-path-version |
      | old              |
      | new              |

  Scenario: Uploading a normal file should not list any activities in the Antivirus section
    Given user "user1" has uploaded file "filesForUpload/lorem.txt" to "/text.txt"
    When the user browses to the activity page
    And the user filters activity list by "Antivirus"
    Then the activity list should be empty
