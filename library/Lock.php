<?php
namespace packages\locked_process;

use packages\base\Date;

/**
 * @phpstan-type LockSerialization array{pid?:int,startTime?:int,endTime?:int,lockIfAlive?:bool}
 */
class Lock implements \JsonSerializable {
	public ?int $pid = null;
	public ?int $startTime = null;
	public ?int $endTime = null;
	public bool $lockIfAlive = true;


	public function isValid(): bool {
		if (!$this->pid) {
			return false;
		}
		if ($this->lockIfAlive) {
			return file_exists('/proc/' . $this->pid);
		}
		return $this->endTime === null or $this->endTime > Date::time();
	}

	/**
	 * @return LockSerialization
	 */
	public function jsonSerialize(): array {
		$data = [];
		foreach (['pid', 'startTime', 'endTime', 'lockIfAlive'] as $key) {
			if ($this->{$key} !== null) {
				$data[$key] = $this->{$key};
			}
		}
		return $data;
	}

	/**
	 * @return LockSerialization
	 */
	public function __serialize(): array {
		return $this->jsonSerialize();
	}

	/**
	 * @param LockSerialization $data
	 */
	public function __unserialize(array $data): void {
		if (isset($data['pid'])) {
			$this->pid = $data['pid'];
		}
		if (isset($data['startTime'])) {
			$this->startTime = $data['startTime'];
		}
		if (isset($data['endTime'])) {
			$this->endTime = $data['endTime'];
		}
		if (isset($data['lockIfAlive'])) {
			$this->lockIfAlive = $data['lockIfAlive'];
		}
	}
}
