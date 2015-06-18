<?php
namespace noelbosscom;
// -------------------------------------------------------------------------------------------
// Deeploi 0.0.1
// https://github.com/noelboss/deeploi
//
// Do not change this file - use config/customisation.php for your customisations
// -------------------------------------------------------------------------------------------

	define( 'BASE', __DIR__ . '/' );

	class Deeploi {
		private $config;
		private $logfile = './logs/deeploi.log';
		
		/*
		$data = file_get_contents('php://input');
		$hook->run(json_decode($data)); */

		public function __construct() {
			$this->config = json_decode( file_get_contents( BASE . 'config/config.json' ) );
			
			$this->testConfig();
			
			$this->run();
		}
		
		private function run() {
			echo "running";
		}
		
		private function testConfig(){
			$conf = $this->config;
			var_dump($conf);
			
			if(strlen($conf->security->token) < 1) {
				$this->log('Error: Please provide security token in config.json', true);
			} else if(strlen($conf->security->token) < 30) {
				$this->log('Warning: Security token unsave, make it longer.');
			}
		}
		
		private function log($msg, $die = false){
			$pre  = date('Y-m-d H:i:s').' (IP: ' . $_SERVER['REMOTE_ADDR'] . '): ';
			file_put_contents($this->logfile, $pre . $msg . "\n", FILE_APPEND);
			if($die){
				die();
			}
		}
	}


	if(file_exists(BASE . 'config/customisation.php')){
		include_once( BASE . 'config/customisation.php' );
	} else {
		$Deeploy = new Deeploi();
	}
