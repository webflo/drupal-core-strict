<?php

use Gitonomy\Git\Reference\Branch;
use Gitonomy\Git\Reference\Tag;
use Gitonomy\Git\Repository;

class Dumper {

  /**
   * @var Gitonomy\Git\Reference\Tag|Gitonomy\Git\Reference\Branch
   */
  protected $reference;

  protected $package;

  protected $repository;

  protected $branch;

  protected $tag;

  protected $name;

  public function __construct(Gitonomy\Git\Reference $reference, array $package, Repository $repository) {
    if ($reference instanceof Tag || $reference instanceof Branch) {
      $this->reference = $reference;
    }
    else {
      throw new \InvalidArgumentException('$ref is not a tag or branch.');
    }

    $this->package = $package;
    $this->repository = $repository;
  }

  protected function getBranch(Gitonomy\Git\Reference $reference) {
    if ($reference instanceof Branch) {
      $this->branch = str_replace('origin/', '', $reference->getName());
      $this->tag = NULL;
      $this->name = $this->branch;
    }
    elseif ($reference instanceof Tag) {
      $branch = explode('.', $reference->getName());
      $this->branch = implode('.', [$branch[0], $branch[1], 'x']);
      $this->tag = $reference;
      $this->name = $this->tag->getName();
    }
  }

  public function write() {
    $this->getBranch($this->reference);
    $wc = $this->repository->getWorkingCopy();
    if (!$this->repository->getReferences()->hasRemoteBranch('origin/' . $this->branch)) {
      $this->repository->run('checkout', ['--orphan', $this->branch]);
      $this->repository->run('rm', ['--cached', '-r', '-f', '.']);
    }
    else {
      $wc->checkout($this->branch);
    }

    // Tag already exists.
    if (isset($this->tag) && $this->repository->getReferences()->hasTag($this->tag->getName())) {
      return;
    }

    file_put_contents($this->repository->getPath() . '/composer.json', json_encode($this->package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $this->repository->run('add', ['composer.json']);

    if (isset($this->tag)) {
      $this->repository->run('commit', ['--allow-empty', '-m', $this->commitMessage()]);
      $this->repository->run('tag', [$this->tag->getName()]);
    }
    elseif (!empty($wc->getDiffStaged()->getFiles())) {
      $this->repository->run('commit', ['-m', $this->commitMessage()]);
    }
  }

  protected function commitMessage() {
    $msg[] = "Update composer.json ({$this->name})";
    $msg[] = '';
    $msg[] = 'Reference: http://cgit.drupalcode.org/drupal/commit/?id=' . $this->reference->getCommitHash();
    return implode("\n", $msg);
  }

}
