<?php

class ValidationResult {

	public bool $success;
	public string $message;
	public string $groupId;

	public static function get(bool $success, string $message = '', string $groupId = '') {
		$res = new ValidationResult();
		$res->success = $success;
		$res->message = $message;
		$res->groupId = $groupId;
		return $res;
	}
}

