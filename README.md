Garnet DeGelder's Simple Score Tracker
======================================

About
-----

This is a simple score tracker, created to allow for input by multiple people at the same time. It supports:
- multiple clubs with multiple teams
- multiple events
- per-event rankings
- per-team rankings
- per-club overall rankings
- point multipliers on events for overall calculations (if an event should not count or count more in the overall results)
- export to and import from CSV files
- data backup and restore
- multiple concurrent users (refresh the page to see changes)


Installation Instructions
-------------------------

1. Edit config.php to specify a SQLite3 database file that you wish to use
2. Change the initial username and/or password set in config.php for security
3. Create the database file (ensure the server process has read-write access to both it and the directory it is in.)
4. Open a web browser to the application's location
5. Log in with the username and password set in the configuration file
