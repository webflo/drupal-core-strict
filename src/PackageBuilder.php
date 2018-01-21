<?php

class PackageBuilder {

  protected $lock;

  public function __construct($lock) {
    $this->lock = $lock;
  }

  public static function fromLockfile($path) {
    return new static(json_decode(file_get_contents($path), TRUE));
  }

  protected function defaultMetadata() {
    return [
      'name' => 'webflo/drupal-core-strict',
      'type' => 'metapackage',
      'description' => 'Locked core dependencies',
      'license' => 'GPL-2.0-or-later',
    ];
  }

  public function buildPackage() {
    $composer = $this->defaultMetadata();

    foreach ($this->lock['packages'] as $package) {
      $composer['require'][$package['name']] = $this->packageToVersion($package);
    }

    foreach ($this->lock['packages-dev'] as $package) {
      $composer['require-dev'][$package['name']] = $this->packageToVersion($package);
    }

    return $composer;
  }

  public function packageToVersion(array $package) {
    if (substr($package['version'], 0, 4) == 'dev-') {
      return $package['version'] . '#' . $package['source']['reference'];
    }
    return $package['version'];
  }

}
