<?php
namespace noelbosscom;
// -------------------------------------------------------------------------------------------
// Deeploi 0.0.1
// https://github.com/noelboss/deeploi
//
// Do not change this file - use config/customisation.php for your customisations
// -------------------------------------------------------------------------------------------

	class Deeploi {
		private $config;
		private $logfile = BASE.'/logs/deeploi.log';
		private $token;
		private $ip;

		/*
		$data = file_get_contents('php://input');
		$hook->run(json_decode($data)); */

		public function __construct() {
			if(isset($_ENV["ENVIRONMENT"]) && file_exists(BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json')){
				$conffile = BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json';
			}
			else {
				$conffile = BASE . 'config/config.json';
			}
			$this->config = json_decode( file_get_contents( $conffile ) );
			$this->token = substr($_SERVER['REQUEST_URI'],1);
			$this->ip = $_SERVER['REMOTE_ADDR'];

			$this->security();

			$this->run();
		}

		private function run() {
			echo "running";
		}

		private function security(){
			$conf = $this->config;

			// check conf
			if(!is_object($conf)){
				$this->log('Error: config.json broken or missing', true);
			}

			// check token
			if(strlen($conf->security->token) < 1) {
				$this->log('Error: Please provide security token in config.json', true);
			} else if(strlen($conf->security->token) < 30) {
				$this->log('Security Warning: Token unsave, make it longer');
			}
			if(!$this->token){
				$this->log('Error: Token not provided. Add the token to the request '.$_SERVER["HTTP_HOST"]."/YOUR-SAVE-TOKEN", true);
			} else if($this->token !== $conf->security->token){
				$this->log('Error: Token not correct: '.$this->token, true);
			} else {
				$this->log('Note: Token correct: '.$this->token);
			}

			// check ip
			if(is_object($conf->security->allowedips)) {
				if(count($conf->security->allowedips)<1) {
					$this->log('Security Warning: Please configure allowed IPs');
				} else {
					if ( !property_exists($conf->security->allowedips, $this->ip )){
						$this->log('Error: IP not allowed: '.$this->ip, true);
					}
				}
			} else if(strlen($conf->security->allowedips) < 7){
				$this->log('Security Warning: Please configure allowed IPs');
			} else {
				if ( $conf->security->allowedips !== $this->ip){
					$this->log('Error: IP not allowed: '.$this->client_ip, true);
				}
			}
		}


		private function log($msg, $die = false){
			$pre  = date('Y-m-d H:i:s').' (IP: ' . $_SERVER['REMOTE_ADDR'] . '): ';
			file_put_contents($this->logfile, $pre . $msg . "\n", FILE_APPEND);
			if($die) die();
		}
	}


	if(file_exists(BASE . 'config/customisation.php')){
		include_once( BASE . 'config/customisation.php' );
	} else {
		$Deeploy = new Deeploi();
	}
