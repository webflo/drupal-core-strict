#!/usr/bin/env sh
mkdir -p tmp

if [ ! -d "tmp/drupal" ]; then
  git clone --branch 8.0.x https://git.drupal.org/project/drupal.git tmp/drupal
fi

if [ ! -d "tmp/metapackage" ]; then
  git clone --branch master https://github.com/webflo/drupal-core-strict tmp/metapackage
fi

php build.php
