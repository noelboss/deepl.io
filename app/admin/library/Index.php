<?php

include_once( 'Utils.php' );
include_once( 'User.php' );

class Index {

	private $config;
	private $user;
	private $utils;
	private $logfile = '/logs/deeplio.log';

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
		$script = '
			$("form").hide();
			$(".list-group").on("click","a.list-group-item", function(e){
				$("form").hide();
				var id = "form"+$(this).attr("id"),
					$form = $("form#"+id).detach(),
					$info = $(".info").hide().after($form.fadeIn());
					e.preventDefault();
					location.hash="#"+this.id;

			});
			$(location.hash).click();
		';
		$this->utils->htmlFragmentEnd($script);
	}

	private function htmlList() {
		?>
		<h1>Deploy Settings.
			<span>Hi <?php echo $this->user->getName(); ?>.</span>
		</h1>

		<div class="row">
			<div class="col-md-5">
				<h3>Deployment Jobs</h3>
				<?php
				global $config;
				$this->configCollector( BASE . 'repositories' );
				?>
			</div>
			<div class="col-md-7">
				<div class="info">
					<h3 class="text-right">Edit Deployment Jobs</h3>

					<div class="list-group text-right">
						To edit and test a deployment, click on settings on the left.
					</div>
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
				<a href="#conf<?= $i ?>" id="conf<?= $i ?>" class="list-group-item">
					<h4 class="list-group-item-heading"><?= $conf->project->name ?></h4>
					<p class="list-group-item-text">Repository: <?= $conf->project->repository_ssh_url ?></p>
					<p class="list-group-item-text">Branch: <?= $conf->project->branch ?></p>
					<?php
						if(isset($_POST['deploy'.$i])){
							include_once('Test.php');
							$Test = new Test($this->config);
							echo $Test->response;
						}
					?>
				</a>
				<form method="post" action="/admin/#conf<?= $i ?>" id="formconf<?= $i ?>">
					<h3>Edit "<?= $conf->project->name ?>"
					<button type="submit" class="btn btn-success pull-right" name="save">Save</button>
					</h3>
					<hr/>
					<?php
					// check conf
					if(!is_object($conf)){
						$this->log('Error: '.$file.' broken', true);
					} else {
						?>
						<div class="form-group">
							<label for="name<?= $i ?>" class="control-label">Configuration Name</label>
							<input type="test" class="form-control" id="name<?= $i ?>" name="name" placeholder="Configuration Title" value="<?= $conf->project->name ?>">
						</div>
						<div class="form-group">
							<label for="repo<?= $i ?>" class="control-label">Repository SSH URL</label>
							<input type="email" class="form-control" id="repo<?= $i ?>" name="repo" placeholder="Repository" value="<?= $conf->project->repository_ssh_url ?>">
						</div>
						<div class="form-group">
							<label for="branch<?= $i ?>" class="control-label">Branch</label>
							<input type="test" class="form-control" id="branch<?= $i ?>" name="branch" placeholder="deploy/dev" value="<?= $conf->project->branch ?>">
						</div>
						<div class="form-group">
							<label for="mail<?= $i ?>" class="control-label">e-Mail</label>
							<div class="input-group">
								<span class="input-group-addon">@</span>
								<input type="email" class="form-control" id="mail<?= $i ?>" name="mail" placeholder="nofitication@email.com" value="<?= $conf->notification->mail ?>">
							</div>
						</div>
						<hr/>
						<div class="form-group">
							<label for="script<?= $i ?>" class="control-label">Deploy Script</label>
							<textarea class="form-control" rows="10" id="script<?= $i ?>" name="script"><?php
									if(file_exists($sh)){
										echo file_get_contents($sh);
									}?></textarea>
						</div>
						<div class="form-group">
							<label for="req<?= $i ?>" class="control-label">Test JSON</label>
							<textarea class="form-control" rows="10" id="req<?= $i ?>" name="req"><?php
									if(file_exists($req)){
										echo file_get_contents($req);
									}?></textarea>
						</div>
						<div class="form-group">
							<button type="submit" class="btn btn-warning" name="deploy<?= $i ?>">Test Deploy</button>
							<button type="submit" class="btn btn-success pull-right" name="save">Save</button>
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
