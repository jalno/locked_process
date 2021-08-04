<?php
namespace packages\locked_process;

use packages\base\{Cache, Date, Log, Exception, Process};

class ProcessLock {
	protected string $lockName;
	protected ?int $maxLockTime;
	protected bool $lockIfAlive;

	/**
	 * @param array<string,mixed> $args
	 */
	public function __construct(array $args, ?int $maxLockTime = null, bool $lockIfAlive = false, ?string $lockName = null) {
		if ($lockName === null) {
			$lockName = $this->findLockName(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
		}
		$this->lockName = $lockName;
		$this->lockIfAlive = $lockIfAlive;
		$this->maxLockTime = $maxLockTime;
		$this->processArgs($args);
	}
	
	public function __destruct() {
		$log = Log::getIntance();
		if ($this->isLocked()) {
			$log->debug("Unlocking process");
			$this->unlock();
			$log->reply("Success");
		}
	}

	public function lock(): void {
		$log = Log::getInstance();

		$log->info("Lock the process " . ($this->maxLockTime ? " until " .  Date::format("Y/m/d H:i:s", Date::time() + $this->maxLockTime) : ""));
		$lock = $this->getLock() ?? new Lock();
		$lock->pid = getmypid() ?: null;
		$lock->startTime = Date::time();
		$lock->endTime = $this->maxLockTime ? Date::time() + $this->maxLockTime : null;
		$lock->lockIfAlive = $this->lockIfAlive;
		Cache::set("packgaes.locked_process.lock." . $this->getLockName(), $lock, $this->maxLockTime ?? 0);
		$log->reply("Success");
	}

	/**
	 * @param array<string,mixed> $args
	 */
	public function processArgs(array $args): void {
		$log = Log::getInstance();

		$lock = $this->getLock();
		$log->debug("Check the process is locked?");
		if ($lock and $lock->isValid()) {
			$log->reply()->warn("it is, pid: {$lock->pid}" . ($lock->endTime ? ", end time: " . Date::format("Y/m/d H:i:s", $lock->endTime) : ""));
			if (!isset($args['force']) or !$args['force']) {
				throw new ProcessLockedException($lock);
			}
			$log->warn("you running the process with --force!");
		} else {
			$log->reply("it's not");
		}
		
		$this->lock();
	}


	public function getLock(): ?Lock {
		return Cache::get("packgaes.locked_process.lock." . $this->getLockName()) ?: null;
	}

	public function isLocked(): bool {
		$lock = $this->getLock();
		return ($lock and $lock->isValid());
	}

	public function unlock(): void {
		Cache::delete("packgaes.locked_process.lock." . $this->getLockName());
	}

	public function getLockName(): string {
		return $this->lockName;
	}

	public function setLockName(string $lockName): void {
		$this->lockName = $lockName;
	}

	/**
	 * @param array[] $traceback
	 */
	protected function findLockName(array $traceback): string {
		foreach ($traceback as $call) {
			/** @var array{"file":string,"line":int,"function"?:string,"class"?:string,"type"?:string} $call */
			if (isset($call['class'], $call['function'], $call['type']) and is_a($call['class'], Process::class, true)) {
				return $call['class'] . $call['type'] . $call['function'];
			}
		}
		throw new Exception("Cannot find the current process");
	}
	
}
