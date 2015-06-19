<?php
namespace noelbosscom;

// ------------------------------------------------------------------------------------
// DEEPLIO 0.0.1
// https://github.com/noelboss/deepl.io
//
// Do not change this file - use config/customisation.php for your customisations
// ------------------------------------------------------------------------------------


	define( 'BASE', __DIR__ . '/' );
	define( 'repositories', __DIR__ . '/repositories/' );

	class DEEPLIO {
		private $config;
		private $logfile;
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

			if(isset($this->config->log)){
				$this->logfile = BASE.'/'.$this->config->log;
			}

			$this->token = substr($_SERVER['REQUEST_URI'],1);
			$this->ip = $_SERVER['REMOTE_ADDR'];

			$this->log('START – Request detected');

			$this->security();
			$this->run();
		}

		private function run() {
			$data = json_decode( file_get_contents('php://input') );
			if($data === null || !is_object($data->repository)){
				$this->log('Error: JSON data missing or broken: '.$data, true);
			}

			$this->log('Note: New push from '.$data->repository->git_http_url);

			$repo = basename($data->repository->git_http_url);
			$branch = str_replace('refs/heads','', $data->ref);

			$path = repositories.$repo.$branch;

			if(file_exists($path.'/config.json')){

				$conf = json_decode( file_get_contents( $path.'/config.json' ) );
				if($conf === null || !is_object($conf)){
					$this->log('Error: '.$path.'/config.json broken', true);
				}

				if ($data->repository->url !== $conf->project->repository){
					$this->log('Error: Repository not matching;');
					$this->log(' - Config: '.$conf->project->repository);
					$this->log(' - Hook: '.$data->repository->url , true);
				}

				if ($data->ref !== 'refs/heads/'.$conf->project->branch){
					$this->log('Branch not configured: Repository not matching;');
					$this->log(' - Config: refs/heads/'.$conf->project->branch);
					$this->log(' - Hook: '.$data->ref, true);
				}

				if(!file_exists($path.'/script.sh')){
					$this->log('Error: No deployment script configured: '.$path.'/script.sh', true);
				} else {
					exec($path.'/script.sh', $out, $ret);
					if ($ret){
						$this->log('Error: Error executing command: ');
						$this->log("   return code $ret", true);
					} else {
						$this->log('SUCCESS – Deployment finished.');
					}
				}

			} else {
				$this->log('Error: No deployment configured: '.$path.'/script.sh', true);
			}
		}

		private function security(){
			$conf = $this->config;

			// check conf
			if(!is_object($conf)){
				$this->log('Error: config.json broken or missing', true);
			}

			// check token
			if(!isset($conf->security->token) || strlen($conf->security->token) < 1) {
				$this->log('Error: Please provide security token in config.json', true);
			} else if(strlen($conf->security->token) < 30) {
				$this->log('Warning: Token unsave, make it longer');
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
					$this->log('Warning: Please configure allowed IPs');
				} else {
					$ips = (array) $conf->security->allowedips;
					if ( !$ips[$this->ip] ){
						$this->log('Error: IP not allowed: '.$this->ip, true);
					}
				}
			} else if(strlen($conf->security->allowedips) < 3){
				$this->log('Warning: Please configure allowed IPs');
			} else {
				if ( $conf->security->allowedips !== $this->ip){
					$this->log('Error: IP not allowed: '.$this->client_ip, true);
				}
			}
		}


		private function log($msg, $die = false){
			if(isset($this->config->log)){
				$pre  = date('Y-m-d H:i:s').' (IP: ' . $_SERVER['REMOTE_ADDR'] . ') ';
				// echoing for manual deploy
				if($_SERVER["SERVER_ADDR"] === $_SERVER["REMOTE_ADDR"] && file_get_contents('php://input')){
					echo $pre . $msg . "\n";
				}
				file_put_contents($this->logfile, $pre . $msg . "\n", FILE_APPEND);
			}
			if($die && !($_SERVER["SERVER_ADDR"] === $_SERVER["REMOTE_ADDR"] && file_get_contents('php://input'))) {
				header("HTTP/1.0 404 Not Found - Archive Empty");
				die();
			} else if($die){
				die();
			}
		}
	}


	if(file_exists(BASE . 'config/customisation.php')){
		include_once( BASE . 'config/customisation.php' );
	} else {
		$Deeplio = new DEEPLIO();
	}
