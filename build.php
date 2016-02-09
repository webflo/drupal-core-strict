<?php

use Gitonomy\Git\Reference\Branch;
use Gitonomy\Git\Reference\Tag;
use Gitonomy\Git\Repository;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/PackageBuilder.php';
require __DIR__ . '/src/Dumper.php';

/**
 * @var Branch[] $branches
 */
$branches = [];

/**
 * @var Tag[] $tags
 */
$tags = [];

$repository = new Repository('tmp/drupal');
$repository->run('fetch');

$metapackage_repository = new Repository('tmp/metapackage');
$metapackage_repository->run('config', ['user.name', 'Florian Weber (via CircleCI)']);
$metapackage_repository->run('config', ['user.email', 'florian@webflo.org']);

$branches = array_filter($repository->getReferences()->getRemoteBranches(), function (Branch $branch) {
  if ($branch->isRemote() && preg_match('/^origin\/8\./', $branch->getName(), $matches)) {
    return TRUE;
  }
  return FALSE;
});

$tags = array_filter($repository->getReferences()->getTags(), function (Tag $tag) {
  return preg_match('/^8\.[0-9]+\.[0-9]+/', $tag->getName());
});

$refs = $tags + $branches;
$refs_array = [];

foreach ($refs as $ref) {
  $name = str_replace('origin/', '', $ref->getName());
  if ($ref instanceof Branch) {
    $name .= '-dev';
  }
  $refs_array[$name] = $ref;
}

$sorted = \Composer\Semver\Semver::sort(array_keys($refs_array));
foreach ($sorted as $version) {
  /** @var Gitonomy\Git\Reference\Tag|Gitonomy\Git\Reference\Branch $ref */
  $ref = $refs_array[$version];
  $repository->run('reset', ['--hard', $ref->getCommitHash()]);
  $path = $repository->getPath() . '/composer.lock';
  if (file_exists($path)) {
    $packageBuilder = PackageBuilder::fromLockfile($path);
    $dump = new Dumper($ref, $packageBuilder->buildPackage(), $metapackage_repository);
    $dump->write();
  }
}

$metapackage_repository->run('push', ['--all', 'origin']);
$metapackage_repository->run('push', ['--tags', 'origin']);
