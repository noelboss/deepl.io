<?php
namespace noelbosscom;

/* ------------------------------------------------------------------------------------
 * Deepl.io – Version 0.2.0
 * https://github.com/noelboss/deepl.io
 *
 * Do not change this file - use config/customisation.php for your customisations
 * ------------------------------------------------------------------------------------ */


	define( 'BASE', __DIR__ . '/' );
	define( 'repositories', __DIR__ . '/repositories/' );

	class DEEPLIO {
		private $config;
		private $logfile;
		private $token;
		private $ip;
		private $service;
		private $data;
		private $log;
		private $projectconf;

		public function __construct() {
			if(isset($_ENV["ENVIRONMENT"]) && file_exists(BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json')){
				$conffile = BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json';
			}
			else {
				$conffile = BASE . 'config/config.json';
			}

			$this->log($_SERVER);

			$this->config = json_decode( file_get_contents( $conffile ) );

			if(isset($this->config->log)){
				$this->logfile = BASE.'/'.$this->config->log;
			}

			$this->token = substr($_SERVER['REQUEST_URI'],1);
			$this->ip = $_SERVER['REMOTE_ADDR'];

			$this->log('START – Request detected');

			$this->data = file_get_contents('php://input');

			$this->service = (strpos($this->data, 'github.com') !== false) ? 'github' : 'gitlab';

			$this->data = json_decode( file_get_contents('php://input') );
			if($this->data === null || !is_object($this->data->repository)){
				$this->log('Error: JSON data missing or broken: '.$this->data, true);
			}

			$this->security();
			$this->run();
		}

		private function run() {
			if($this->service === 'github'){
				$repo = $this->data->repository->ssh_url;
			} else {
				$repo = $this->data->repository->git_ssh_url;
			}

			$this->log('Note: New push from '.$repo);

			$branch = str_replace('refs/heads/','', $this->data->ref);
			$branch = str_replace('/','-', $branch);

			$path = repositories.basename($repo).'/'.$branch;
			if(file_exists($path.'.config.json')){

				$conf = json_decode( file_get_contents( $path.'.config.json' ) );
				$this->projectconf = $conf;

				if($conf === null || !is_object($conf)){
					$this->log('Error: '.$path.'.config.json broken', true);
				}

				if ($repo !== $conf->project->repository_ssh_url){
					$this->log('Error: Repository not matching;');
					$this->log(' - Config: '.$conf->project->repository_ssh_url);
					$this->log(' - Hook: '.$repo , true);
				}

				if ($this->data->ref !== 'refs/heads/'.$conf->project->branch){
					$this->log('Branch not configured: Repository not matching;');
					$this->log(' - Config: refs/heads/'.$conf->project->branch);
					$this->log(' - Hook: '.$this->data->ref, true);
				}

				// using php over shell (since you can call shell from php)
				if(file_exists($path.'.script.php')){
					try {
						chdir(BASE);
						$this->log('Note: Using PHP '.$path.'.script.php:');
						include_once($path.'.script.php');
						$this->success();
					} catch (Exception $e) {
						$this->log('Error: Error in '.$path.'.script.php:');
						$this->log('   '.$e);
					}
				}
				// no shell and no php? no deployment
				else if(!file_exists($path.'.script.sh')){
					$this->log('Error: No deployment script configured: '.$path.'.script.sh', true);
				} else {
					// change to root directory
					chdir(BASE);
					exec($path.'.script.sh', $out, $ret);
					if ($ret){
						$this->log('Error: Error executing command in '.$path.'.script.sh:');
						$this->log("   return code $ret", true);
					} else {
						$this->success();
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
					if ( !isset($ips[$this->ip]) ){
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

		private function success(){
			$this->log('SUCCESS – Deployment finished.');
			$this->notification(true);
		}

		private function notification($success = false){
			$conf = $this->projectconf;

			$to = $conf->notification->mail;
			$status = $success ? 'SUCCESS' : 'FAILED';
			$this->log('Sending mail to: '.$to);

			$subject = '['.$conf->project->name.'] Deepl.io status: '.$status;

			$message = "This is the protocol of your deployment:<br>";
			$message .= nltbr($this->log);

			$headers = "From: " . strip_tags('noreply@deepl.io') . "\r\n";
			$headers .= "Reply-To: ". strip_tags($to) . "\r\n";
			//$headers .= "CC: susan@example.com\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

			mail($to, $subject, $message, $headers);
		}

		private function log($msg, $die = false){
			if(isset($this->config->log)){
				$pre  = date('Y-m-d H:i:s').' (IP: ' . $_SERVER['REMOTE_ADDR'] . ') ';
				// echoing for manual deploy
				if($_SERVER["SERVER_ADDR"] === $_SERVER["REMOTE_ADDR"] && file_get_contents('php://input')){
					echo $pre . $msg . "\n";
				}
				file_put_contents($this->logfile, $pre . $msg . "\n", FILE_APPEND);
				$this->log .= $pre . $msg . "\n";
			}
			if($die && !($_SERVER["SERVER_ADDR"] === $_SERVER["REMOTE_ADDR"] && file_get_contents('php://input'))) {
				header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
				$this->notification();
				die();
			} else if($die){
				$this->notification();
				die();
			}
		}
	}


	if(file_exists(BASE . 'config/customisation.php')){
		include_once( BASE . 'config/customisation.php' );
	} else {
		$Deeplio = new DEEPLIO();
	}