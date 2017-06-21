To fix to dependencies tested on Drupal 8.3.0 add ```"webflo/drupal-core-strict": "8.3.0"``` to the require section of your composer.json and run ```composer update``` from the command line.

In other words: this is a _virtual_ package, that causes you to get exactly the versions of Drupal core's dependencies as they are specified in Drupal core's `composer.lock` file.

Using ```composer require``` is problematic on an existing site.
