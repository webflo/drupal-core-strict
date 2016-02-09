#!/usr/bin/env sh
php build.php
cd tmp/metapackage
git push --all origin
git push --tags origin
