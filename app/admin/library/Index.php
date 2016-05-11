<?php
namespace noelbosscom;

include_once( 'Utils.php' );
include_once( 'User.php' );
include_once( './../../incl/Helpers.php' );
define( 'REPOS', BASE.'repositories/' );

class Index {

	private $Helpers;
	private $User;
	private $Utils;

	private $config;
	private $logfile;
	private $repositoriesPath;

	function __construct() {
		$this->User= new User();
		$this->Utils = new Utils();
		$this->logfile =  BASE . '/logs/deeplio.log';


		if(isset($_ENV["ENVIRONMENT"]) && file_exists(BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json')){
			$conffile = BASE . 'config.'.$_ENV["ENVIRONMENT"].'/config.json';
		}
		else {
			$conffile = BASE . 'config/config.json';
		}

		if(!file_exists($conffile)){
			$this->log('[ERROR] Config file missing: '.$conffile, true);
		}
		$this->config = json_decode( file_get_contents( $conffile ) );
		if($this->config  === null || !is_object($this->config)){
			$this->log('[ERROR] Config broken: '.$conffile, true);
		}

		$this->Utils->htmlFragmentStart( 'Deepl.io Settings' );
		$this->htmlList();

		$this->Utils->htmlFooter();

		$script = '
			$("form").hide();
			$(".list-group").on("click","a.list-group-item", function(e){
				$("form").hide();
				var id = "form"+$(this).attr("data-id"),
					$form = $("form#"+id).detach(),
					$info = $(".info").hide().after($form.fadeIn());
					location.hash="#"+$(this).attr("data-id");
					return false;
			});
			if(location.hash && $(location.hash+"link").length){
				$(location.hash+"link").click();
			}
		';
		$this->Utils->htmlFragmentEnd($script);
	}

	private function htmlList() {
		?>
		<h1>Deploy Settings.
			<span>Hi <?php echo $this->User->getName(); ?>.</span>
		</h1>

		<div class="row">
			<div class="col-md-5">
				<h3>Deployment Jobs</h3>
				<?php
				global $config;
				$this->repositoriesPath = isset($this->config->repositoriesPath) && is_dir(BASE.$this->config->repositoriesPath) ? BASE.$this->config->repositoriesPath : REPOS;
				$this->configCollector( $this->repositoriesPath );
				?>
			</div>
			<div class="col-md-7">
				<div class="info">
					<h3 class="text-right">Edit Deployment Jobs</h3>

					<div class="list-group text-right">
						To edit and test a deployment, click Settings on the left.
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

				$this->Helpers = new Helpers();
				$to = htmlspecialchars($this->Helpers->getMails($conf));

				if($to === false){
					$this->log('[NOTR] No mails configured.');
					return false;
				}

				?>
				<a href="#conf<?= $i ?>" id="conf<?= $i ?>link" data-id="conf<?= $i ?>" class="list-group-item">
					<h4 class="list-group-item-heading"><?= $conf->project->name ?></h4>
					<p class="list-group-item-text"><strong>Repository:</strong> <?= $conf->project->repository_ssh_url ?></p>
					<p class="list-group-item-text"><strong>Branch:</strong> <?= $conf->project->branch ?></p>
					<p class="list-group-item-text"><strong>Mails:</strong> <?= $to ?></p>
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
							<input type="text" class="form-control" id="name<?= $i ?>" name="name" placeholder="Configuration Title" value="<?= $conf->project->name ?>">
						</div>
						<div class="form-group">
							<label for="repo<?= $i ?>" class="control-label">Repository SSH URL</label>
							<input type="url" class="form-control" id="repo<?= $i ?>" name="repo" placeholder="Repository" value="<?= $conf->project->repository_ssh_url ?>">
						</div>
						<div class="form-group">
							<label for="branch<?= $i ?>" class="control-label">Branch</label>
							<input type="text" class="form-control" id="branch<?= $i ?>" name="branch" placeholder="deploy/dev" value="<?= $conf->project->branch ?>">
						</div>
						<div class="form-group">
							<label for="mail<?= $i ?>" class="control-label">Mails</label>
							<div class="input-group">
								<span class="input-group-addon">@</span>
								<input type="email" class="form-control" id="mail<?= $i ?>" name="mail" placeholder="nofitication@email.com" value="<?= $to ?>">
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
		if(file_exists($this->logfile)){
			file_put_contents($this->logfile, $pre . $msg . "\n", FILE_APPEND);
		} else {
			echo $pre . $msg . "<br/>";
		}
		if($die) die();
	}
}
