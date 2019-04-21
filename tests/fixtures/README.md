# Fixtures README

These fixtures are automatically copied to a temporary directory during test runs. After the test run, the fixtures are automatically deleted.

Set the SCAFFOLD_FIXTURE_DIR environment variable to place the fixtures in a specific location rather than a temporary directory. If this is done, then the fixtures will not be deleted after the test run. This is useful for ad-hoc testing.

Example:

$ SCAFFOLD_FIXTURE_DIR=$HOME/tmp/scaffold-fixtures composer unit
$ cd $HOME/tmp/scaffold-fixtures
$ cd drupal-drupal
$ composer composer:scaffold

