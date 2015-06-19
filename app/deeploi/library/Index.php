<?php

include_once( 'Utils.php' );
include_once( 'User.php' );

class Index {

	private $user;
	private $utils;
	private $logfile = BASE.'/logs/deeploi.log';

	function __construct() {
		$this->user  = new User();
		$this->utils = new Utils();

		$this->utils->htmlFragmentStart( 'Deeploi Start' );
		$this->htmlList();
		$this->utils->htmlFragmentEnd( '' );
	}

	private function htmlList() {
		?>
		<h1>Deeploi Settings.
			<span>Hi <?php echo $this->user->getName(); ?>.</span>
		</h1>

		<div class="row">
			<div class="col-md-6">
				<h3>Deployment Configurations</h3>
				<?php
				global $config;
				$this->configCollector( BASE . 'projects' );
				?>
			</div>
			<div class="col-md-6">
				<h3>Tools</h3>

				<div class="list-group">
					<?php
					global $config;
					foreach ( $config->micro->components as $key => $component ) {
						echo '<a href="' . TERRIFICURL . 'create/' . $key . '" class="list-group-item">';
						echo 'Create ' . ucfirst( $key );
						echo '</a>';
					}
					?>
				</div>
			</div>
		</div>
	<?php
	}


	private function configCollector( $base, $folders = array() ) {
		global $config;

		// views
		$files = glob( $base . '/*.json');
		$this->viewList( $files, $folders );
	}

	private function viewList( $files, $dirs = null ) {
		?>
		<div class="list-group">
			<?php
			foreach ( $files as $file ) {
				$conf = json_decode( file_get_contents( $file ) );
				$sh = dirname($file).'/'.basename($file, '.json').'.sh';

				// check conf
				if(!is_object($conf)){
					$this->log('Error: '.$file.' broken', true);
				} else {
					echo "<p><input type='text' value='".$conf->project->name."' /></p>";
					echo "<p><input type='text' value='".$conf->project->repository."' /></p>";
					echo "<p><input type='text' value='".$conf->project->branch."' /></p>";
					echo "<textarea>";
					if(file_exists($sh)){
						echo file_get_contents( $sh );
					}
					echo "</textarea>";
				}
			}
			?>
		</div>
	<?php
	}

	private function log($msg, $die = false){
		$pre  = date('Y-m-d H:i:s').' (IP: ' . $_SERVER['REMOTE_ADDR'] . '): ';
		file_put_contents($this->logfile, $pre . $msg . "\n", FILE_APPEND);
		if($die) die();
	}
}
