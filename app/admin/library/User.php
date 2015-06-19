<?php

class User {

	private $user;
	private $email;

	function __construct($user = NULL, $email = NULL) {
		if(!empty($user)){
			$this->user = $user;
		}
		if(!empty($email)){
			$this->email = $email;
		}

		if (empty($this->user)) {
			exec('git config --get user.name', $user, $status);
			if ($status === 0) {
				$this->user = $user[0];
			}
		}
		if (empty($this->email)) {
			exec('git config --get user.email', $email, $status);
			if ($status === 0) {
				$this->email = $email[0];
			}
		}
	}

	public function getName(){
		return $this->user;
	}

	public function getEmail(){
		return $this->email;
	}
}