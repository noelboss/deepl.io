<?php
namespace noelbosscom;

/* ------------------------------------------------------------------------------------
 * Deepl.io – Version 0.10.1
 * https://github.com/noelboss/deepl.io
 *
 * Do not change this file - use config/customisation.php for your customisations
 * ------------------------------------------------------------------------------------ */


	define( 'BASE', __DIR__ . '/../../' );
	define( 'REPOS', BASE.'repositories/' );

	include_once('../incl/Helpers.php');
    include_once('../incl/IpAddress.php');
    include_once('../incl/IpRange.php');

	class Deeplio {
		private $Helpers;

		private $config;
		private $logfile;
		private $token;
		private $ip;
		private $service;
		private $data;
		private $log;
		private $projectConf;

		private $cachePath;
		private $cacheFile;
		private $cacheFileBefore;

		private $repositoriesPath;

		private $isManualDeploy;

		public function __construct() {
			if(isset($_ENV["ENVIRONMENT"]) && is_file(BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json')){
				$confFile = BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json';
			}
			else {
				$confFile = BASE . 'config/config.json';
			}

			$this->config = json_decode( file_get_contents( $confFile ) );

			if(isset($this->config->log)){
				$this->logfile = BASE.$this->config->log;
				if(!is_dir(dirname($this->logfile))) mkdir(dirname($this->logfile));
			}

			if(isset($this->config->cachePath)){
				$this->cachePath = BASE.$this->config->cachePath;
				if(!is_dir($this->cachePath)) mkdir($this->cachePath);
			}


			$this->repositoriesPath = isset($this->config->repositoriesPath) && is_dir(BASE.$this->config->repositoriesPath) ? BASE.$this->config->repositoriesPath : REPOS;

			// using github secret or url
			$request = explode('/',$_SERVER['REQUEST_URI']);
			$this->token = isset($_SERVER['HTTP_X_HUB_SIGNATURE']) ? $_SERVER['HTTP_X_HUB_SIGNATURE'] : end($request);

			$this->ip = new IpAddress($_SERVER['REMOTE_ADDR']);

			$this->log('');
			$this->log('[START] Request detected');
			$this->log('–––––––––––––––––––––––––––––––––');
			$this->log(date('[Y-m-d H:i:s').' - IP ' . $_SERVER['REMOTE_ADDR'] . ']');
			//$this->log('[TOKEN] '.$this->token);

			$raw = file_get_contents('php://input');
			$this->isManualDeploy = $_SERVER["SERVER_ADDR"] === $_SERVER["REMOTE_ADDR"] && $raw;
			$this->service = $this->getService();

			$this->data = json_decode( $raw );
			if($this->data === null || !is_object($this->data->repository)){
				$this->log('[ERROR] JSON data missing or broken: '.$this->data, true);
			}

			$this->cacheFile = $this->cachePath.substr($this->data->after, -12);
			$this->cacheFileBefore = $this->cachePath.substr($this->data->before, -12);

			$this->security();
			$this->run();
		}

		private function run() {
		    if ($this->service === 'Bitbucket'){
		        // Get repository name
                $scm = $this->data->repository->scm;
                $fullName = $this->data->repository->full_name;
                $repo = "$scm@bitbucket.org:$fullName.$scm";

                foreach ($this->data->push->changes as $change) {
                    $branch = '';
                    $before = '';
                    if (is_object($change->old)){
                        $before = $change->old->target->hash;
                        $branch = $change->old->name;
                    }
                    $after = '';
                    if (is_object($change->new)){
                        $after = $change->new->target->hash;
                        $branch = $change->new->name;
                    }
                    
                    $this->handle($repo, $branch, $before, $after);
		        }
            } else {
                if ($this->service === 'GitHub') {
                    $repo = $this->data->repository->ssh_url;
                } else {
                    $repo = $this->data->repository->git_ssh_url;
                }

                $branch = str_replace('refs/heads/','', $this->data->ref);
                $branch = str_replace('/','-', $branch);

                $this->handle($repo, $branch, $this->data->before, $this->data->after);
            }
        }

        /**
         * Handles the deploy of a repository.
         *
         * This method will not abort the request since bitbucket payload can contain information about
         * multiple branches and we need to find the right one.
         * However, it will end the request if a matching repo/branch is found and the deployment
         * is successful
         *
         * @param $repo string The repo url
         * @param $branch string Name of the changed branch
         * @param $before string The hash of the commit before the change
         * @param $after string The hash of the commit after the change
         */
		private function handle($repo, $branch, $before, $after) {
		    // Check if there is an old version of the before file.
            $fileBase = $this->cachePath . DIRECTORY_SEPARATOR . base64_encode($repo . "." .$branch) . ".";
            $cacheFileBefore = $this->cachePath.'/'.substr($before, -12);
            if (!is_file($cacheFileBefore)){
                $cacheFileBefore = $fileBase . substr($before, -12);
            }

            // New way for cache files, repo and branch specific.
            // Just to be sure we don't mess up
            $this->cacheFile = $fileBase . substr($after, -12);
            $this->cacheFileBefore = $cacheFileBefore;

			$beforeShort = substr($before, 0, 7).'..'.substr($before, -7);
			$afterShort = substr($after, 0, 7).'..'.substr($after, -7);

			$this->log('[NOTE] New push from '.$this->service.":\n  - ".$repo."\n  - From $beforeShort\n  - To $afterShort");


			$path = $this->repositoriesPath.basename($repo).'/'.$branch;
			$debugPath = $this->repositoriesPath.basename($repo).'/'.$branch;

			$this->log('[NOTE] Path: '.basename($repo).'/'.$branch);

			if(is_file($this->cacheFile)){
				$this->log('[NOTE] Commit already deployed: '.$afterShort);
			}
			else if(is_file($path.'.config.json')){

				$this->log('[NOTE] Cache File does not exist '.$this->cacheFile.' . Start Deploying:');

				try {
					$conf = json_decode( file_get_contents( $path.'.config.json' ) );
					$this->projectconf = $conf;
				} catch (Exception $e) {
					$this->log('[ERROR] Could not read data from json: '.$path.'.config.json');
				}
				$conf = json_decode( file_get_contents( $path.'.config.json' ) );
				$this->projectConf = $conf;

				if(!$conf->enabled){
					$this->log('[NOTE] Repository disabled by config');
					// abort function but don't send mails...
					return;
				}

				if($conf === null || !is_object($conf)){
					$this->log('[ERROR] '.$debugPath.'.config.json broken', true);
				}

				if ($repo !== $conf->project->repository_ssh_url){
					$this->log('[ERROR] Repository not matching;');
					$this->log(' - Config: '.$conf->project->repository_ssh_url);
					$this->log(' - Hook: '.$repo , true);
                    // abort function
					return;
				}

				if ($branch !== $conf->project->branch){
					$this->log('Branch not configured: Repository not matching;');
					$this->log(' - Config: '. $conf->project->branch );
					$this->log(' - Hook: '.$branch, true);
                    // abort function
                    return;
				}

				if(is_file($path.'.script.php')){
					try {				// using php over shell (since you can call shell from php)
						chdir(BASE);
						$this->log('[NOTE] Using PHP '.$path.'.script.php:');
						include_once($path.'.script.php');
						$this->success();
					} catch (\Exception $e) {
						$this->log('[ERROR] Error in '.$path.'.script.php:');
						$this->log('   '.$e);
					}
				}
				// no shell and no php? no deployment
				else if(!is_file($path.'.script.sh')){
					$this->log('[ERROR] No deployment script configured: '.$debugPath.'.script.sh / .php', true);
				} else {
					// change to root directory
					chdir(BASE);
					exec($path.'.script.sh', $out, $ret);
					if ($ret){
						$this->log('[ERROR] Error executing command in '.$debugPath.'.script.sh:');
						$this->log("   return code $ret", true);
					} else {
						$this->success();
					}
				}

			} else {
				$this->log('[ERROR] No deployment configured: '.$path.'.config.json', true);
			}
		}

        /**
         * Checks if the request is allowed.
         *
         * Sends the following HTTP Status Codes:
         *
         * 500 Internal Server Error
         *     - If config.json is broken of missing
         *
         * 403 Forbidden
         *     - If the security token is missing
         *     - If the security token is not correct
         *     - If the IP is not allowed
         */
		private function security(){
			$conf = $this->config;

			// check conf
			if(!is_object($conf)){
				$this->log('[ERROR] config.json broken or missing', true, 500);
			}

			// check token
			if(!isset($conf->security->token) || strlen($conf->security->token) < 1) {
				$this->log('[ERROR] Please provide security token in config.json', true, 403);
			} else if(strlen($conf->security->token) < 30) {
				$this->log('[WARNING] Security token unsafe, make it longer');
			}
			if(!$this->token){
				$this->log('[ERROR] Security token not provided. Add the token to the request '.$_SERVER["HTTP_HOST"]."/YOUR-SAVE-TOKEN", true, 403);
			} else if($this->token !== $conf->security->token){
				$this->log('[ERROR] Security token not correct: '.$this->token, true, 403);
			} else {
				$this->log('[NOTE] Security token correct');
			}

			// check ip
			if(is_object($conf->security->allowedIps)) {
				if(count($conf->security->allowedIps)<1) {
					$this->log('Warning: Please configure allowed IPs');
				} else {
					$ips = (array) $conf->security->allowedIps;
					$ipAllowed = false;
                    foreach ($ips as $ip => $allowed) {
                        if ($allowed){
                            $allowedIp = new IpRange($ip);
                            if ($allowedIp->contains($this->ip)){
                                $ipAllowed = true;
                                break;
                            }
                        }
					}
					if (!$ipAllowed){
						$this->log('[ERROR] IP not allowed: '. (string)$this->ip, true, 403);
					}
				}
			} else if(strlen($conf->security->allowedIps) < 3){
				$this->log('Warning: Please configure allowed IPs');
			} else {
			    $allowedIp = new IpRange($conf->security->allowedIps);
				if ( $allowedIp->contains($this->ip)){
					$this->log('[ERROR] IP not allowed: '. (string)$this->ip, true, 403);
				}
			}
		}

        private function getService(){
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            if ((strpos($userAgent, 'GitHub') !== false)) {
                return 'GitHub';
            }
            if ((strpos($userAgent, 'Bitbucket') !== false)) {
                return 'Bitbucket';
            }
            // TODO: make sure it is really from GitLab
            return 'GitLab';
        }

		private function success(){
			$this->log('[STATUS] SUCCESS – Deployment finished.');
			if($this->cachePath){
			    if ($this->cacheFileBefore){
                    if(is_file($this->cacheFileBefore)) unlink($this->cacheFileBefore);
                }
                if ($this->cacheFile) {
                    file_put_contents($this->cacheFile, "");
                }
			}
			$this->mails(true);
			die();
		}

		private function mails($success = false){
			$conf = $this->projectConf;

			$this->Helpers = new Helpers();
			$to = $this->Helpers->getMails($conf);

			if($to === false){
				$this->log('[NOTE] No mails configured.');
				return false;
			}

			$status = $success ? 'SUCCESS' : 'FAILED';
			$lead = $success ? 'High five, your deployment was successful! If you like it, please consider
										<a href="https://twitter.com/intent/tweet?button_hashtag=Deepl.io&text=I%20use%20Deepl.io%20do%20easily%20deploy%20my%20Git%20projects%20using%20webhooks.%20Try%20it,%20it\'s%20great!%20http://deepl.io/%20%20#Deepl.io">tweeting</a>
										or blogging about...' : 'Your project failed to deploy. Check your configuration and deployment script and read the <a href="http://deepl.io">documentation</a> or open up a <a href="https://github.com/noelboss/deepl.io/issues/new">support issue</a>.';
			$this->log('Sending mail to: '.$to);

			$subject = '['.$conf->project->name.'] Deployment Status: '.$status;

			$mail = (object) array(
				'project' => $conf->project->name,
				'status' => $status,
				'class' => $success ? 'success' : 'error',
				'lead' => $lead,
				'log' => nl2br($this->log),
			);

			$message = file_get_contents(BASE.'/assets/Mail.html');
			foreach ($mail as $key => $value) {
				$message = str_replace('{{'.$key.'}}', $value, $message);
			}

			$headers = "From: Deepl.io <" . strip_tags('noreply@deepl.io') . "> \r\n";
			//$headers .= "Reply-To: ". $to . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

			mail($to, $subject, $message, $headers);
		}

		private function log($msg, $sendMails = false, $statusCode = false){
			if(isset($this->config->log)){

				// echoing for manual deploy
				if($this->isManualDeploy){
					echo '– '.htmlspecialchars($msg) . "\n";
				}
				file_put_contents($this->logfile, $msg . "\n", FILE_APPEND);
				$this->log .= '– '.htmlspecialchars($msg) . "\n";
			}
			if($sendMails && !$this->isManualDeploy) {
				$this->log('[STATUS] FAILED – Deployment not finished!');
				$this->mails();
			} else if($sendMails){
				$this->log('[STATUS] FAILED – Deployment not finished!');
				$this->mails();
			}
			if ($statusCode) {
                http_response_code($statusCode);
                die();
            }
		}
	}


	if(is_file(BASE . 'config/customisation.php')){
		include_once( BASE . 'config/customisation.php' );
	} else {
		$Deeplio = new Deeplio();
	}
