<?php
namespace packages\locked_process;

use packages\base\Date;

class Lock implements \JsonSerializable, \Serializable {
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

	public function jsonSerialize() {
		$data = [];
		foreach (['pid', 'startTime', 'endTime', 'lockIfAlive'] as $key) {
			if ($this->{$key} !== null) {
				$data[$key] = $this->{$key};
			}
		}
		return $data;
	}

	public function serialize() {
		return serialize($this->jsonSerialize());
	}

	public function unserialize($serialized) {
		$data = unserialize($serialized);
		foreach (['pid', 'startTime', 'endTime', 'lockIfAlive'] as $key) {
			if (isset($data[$key])) {
				$this->{$key} = $data[$key];
			}
		}
	}
}
