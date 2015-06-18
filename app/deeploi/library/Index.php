<?php

include_once( 'Utils.php' );
include_once( 'User.php' );

class Index {

	private $user;
	private $utils;

	function __construct() {
		$this->user  = new User();
		$this->utils = new Utils();

		$this->utils->htmlFragmentStart( 'Deeploi Start' );
		$this->htmlList();
		$this->utils->htmlFragmentEnd( '' );
	}

	private function htmlList() {
		?>
		<h1>Deeploi Start.
			<span>Hi <?php echo $this->user->getName(); ?>.</span>
		</h1>

		<div class="row">
			<div class="col-md-6">
				<h3>Views</h3>
				<?php
				global $config;
				$this->viewCollector( BASE . $config->micro->view_directory );
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


	private function viewCollector( $base, $folders = array() ) {
		global $config;

		// views
		$files = glob( $base . '/*.' . $config->micro->view_file_extension );
		$this->viewList( $files, $folders );

		// sub folders
		foreach ( glob( $base . '/*', GLOB_ONLYDIR ) as $dir ) {
			$base_dir = basename( $dir );
			if ( $base_dir !== basename( $config->micro->view_partials_directory ) ) {
				$dirs = array_merge( $folders, array( $base_dir ) );

				// more sub folders
				$this->viewCollector($dir, $dirs);
			}
		}
	}

	private function viewList( $files, $dirs = null ) {
		global $config;
		?>
		<div class="list-group">
			<?php
			foreach ( $files as $file ) {
				if ( basename( $file, '.' . $config->micro->view_file_extension ) !== basename( __FILE__, '.' . $config->micro->view_file_extension ) ) {
					?>
					<a href="<?php echo BASEURL, !empty( $dirs ) ? implode( $dirs, '-' ) . '-' : '', basename( $file, '.' . $config->micro->view_file_extension ); ?>" class="list-group-item">
						<?php echo !empty( $dirs ) ? ucwords( implode( $dirs, ' ' ) ) . ' ' : '', ucwords( str_replace( '-', ' ', basename( $file, '.' . $config->micro->view_file_extension ) ) ); ?>
					</a>
				<?php
				}
			}
			?>
		</div>
	<?php
	}
}
