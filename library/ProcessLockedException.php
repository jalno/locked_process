<?php
namespace packages\locked_process;

use packages\base\Exception;

class ProcessLockedException extends Exception {
	protected Lock $lock;

	public function __construct(Lock $lock, string $message = "Process is locked, Run with --force", int $code = 0, ?\Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->lock = $lock;
	}

	public function getLock(): Lock {
		return $this->lock;
	}
}
