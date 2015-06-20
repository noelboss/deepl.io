<?php

include_once( 'Utils.php' );
include_once( 'User.php' );

class Index {

	private $config;
	private $user;
	private $utils;
	private $logfile = BASE.'/logs/deeplio.log';

	function __construct() {
		$this->user= new User();
		$this->utils = new Utils();

		if(isset($_ENV["ENVIRONMENT"]) && file_exists(BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json')){
			$conffile = BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json';
		}
		else {
			$conffile = BASE . 'config/config.json';
		}
		$this->config = json_decode( file_get_contents( $conffile ) );

		$this->utils->htmlFragmentStart( 'Deepl.io Settings' );
		$this->htmlList();
		$this->utils->htmlFragmentEnd( '' );
	}

	private function htmlList() {
		?>
		<h1>Deploy Settings.
			<span>Hi <?php echo $this->user->getName(); ?>.</span>
		</h1>

		<div class="row">
			<div class="col-md-6">
				<h3>Deployment Configurations</h3>
				<?php
				global $config;
				$this->configCollector( BASE . 'repositories' );
				?>
			</div>
			<div class="col-md-6">
				<h3>Help</h3>

				<div class="list-group">
					Here you will find more information...
				</div>
			</div>
		</div>
	<?php
	}


	private function configCollector( $base, $folders = array() ) {
		global $config;

		// views
		$files = glob( $base . '/*.git/*config.json');
		$this->viewList( $files, $folders );
	}

	private function viewList( $files, $dirs = null ) {
		?>
		<div class="list-group">
			<?php
			$i = 0;
			foreach ( $files as $file ) {
				$i++;
				$conf = json_decode( file_get_contents( $file ) );
				$branch = str_replace('/','-',$conf->project->branch);
				$sh = dirname($file)."/".$branch.".script.sh";
				$req = dirname($file)."/".$branch.".request.json";

				?>
				<form class="form-horizontal" method="post" action="/admin/">

					<?php
					if(isset($_POST['repl'.$i])){

						// sending testdata...
						$options = array(
							'http' => array(
								'method'=> 'POST',
								'content' => json_encode( json_decode($_POST['req'.$i]) ),
								'header'=>"Content-Type: application/json\r\n" .
									"Accept: application/json\r\n"
								)
						);

						$url = 'http://'.$_SERVER["HTTP_HOST"].'/'.$this->config->security->token;

						$context = stream_context_create( $options );
						$response = file_get_contents( $url, false, $context );

						$class = 'info';
						$class = strripos($response, 'success') !== false ? 'success' : $class;
						$class = strripos($response, 'warning') !== false ? 'warning' : $class;
						$class = strripos($response, 'error') !== false ? 'danger' : $class;
						?>
						<div class="alert alert-<?= $class ?>" role="alert">
							<h4>Request sent:</h4>
							<pre><?= $response ?></pre>
						</div>
						<?php
					}

					// check conf
					if(!is_object($conf)){
						$this->log('Error: '.$file.' broken', true);
					} else {
						?>
						<div class="form-group">
							<label for="name<?= $i ?>" class="col-sm-2 control-label">Name</label>
							<div class="col-sm-10">
								<input type="email" class="form-control" id="name<?= $i ?>" placeholder="Repository" value="<?= $conf->project->name ?>">
							</div>
						</div>
						<div class="form-group">
							<label for="repo<?= $i ?>" class="col-sm-2 control-label">Repository SSH URL</label>
							<div class="col-sm-10">
								<input type="email" class="form-control" id="repo<?= $i ?>" placeholder="Repository" value="<?= $conf->project->repository ?>">
							</div>
						</div>
						<div class="form-group">
							<label for="branch<?= $i ?>" class="col-sm-2 control-label">Branch</label>
							<div class="col-sm-10">
								<input type="email" class="form-control" id="branch<?= $i ?>" placeholder="deploy/dev" value="<?= $conf->project->branch ?>">
							</div>
						</div>

						<div class="form-group">
							<label for="script<?= $i ?>" class="col-sm-2 control-label">Deploy Script</label>
							<div class="col-sm-10">
								<textarea class="form-control" rows="10" id="script<?= $i ?>">
									<?php
									if(file_exists($sh)){
										echo file_get_contents($sh);
									}
									?>
								</textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="req<?= $i ?>" class="col-sm-2 control-label">Test JSON</label>
							<div class="col-sm-10">
								<textarea class="form-control" rows="10" id="req<?= $i ?>" name="req<?= $i ?>">
									<?php
									if(file_exists($req)){
										echo file_get_contents($req);
									}
									?>
								</textarea>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-10">
								<button type="submit" class="btn btn-default" name="repl<?= $i ?>">Manual Deploy</button>
							</div>
						</div>
					</form>

				<?php
				}
			}
			?>
		</div>
	<?php
	}

	private function log($msg, $die = false){
		$pre= date('Y-m-d H:i:s').' (IP: ' . $_SERVER['REMOTE_ADDR'] . '): ';
		file_put_contents($this->logfile, $pre . $msg . "\n", FILE_APPEND);
		if($die) die();
	}
}
