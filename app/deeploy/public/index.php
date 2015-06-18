<?php
//error_reporting(-1);
//ini_set('display_errors', '1');

$pathes  = explode( '/app/terrific/public', dirname( str_replace( '\\', '/', __FILE__ ) ) );
$uri     = 'http' . ( empty( $_SERVER['HTTPS'] ) ? '' : 's' ) . '://' . $_SERVER['SERVER_NAME'] . ( $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '' ) . $_SERVER['REQUEST_URI'];
$baseuri = substr( $uri, 0, strrpos( $uri, '/terrific/', - 1 ) );

define( 'BASE', $pathes[0] . '/' );
define( 'BASEURL', $baseuri . '/' );
define( 'TERRIFICURL', $baseuri . '/terrific/' );

$config = json_decode( file_get_contents( BASE . 'config.json' ) );

include_once( '../../../project/index.project.php' );

$parts     = isset( $_GET['uriparts'] ) ? explode( '/', $_GET['uriparts'] ) : '';
$nrOfParts = count( $parts );

if ( $nrOfParts > 1 && $parts[0] === 'create' && property_exists( $config->micro->components, $parts[1] ) ) {
	include_once( '../library/Component.php' );

	$componentConfig            = $config->micro->components->$parts[1];
	$componentConfig->component = $parts[1];
	$component                  = isset( $_REQUEST['component'] ) ? $_REQUEST['component'] : null;
	$skin                       = isset( $_REQUEST['skin'] ) ? $_REQUEST['skin'] : null;
	$username                   = isset( $_REQUEST['user'] ) ? $_REQUEST['user'] : null;
	$useremail                  = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : null;

	$page = new Component( $componentConfig, $component, $skin, $username, $useremail );
}
else {
	// index page (overview)
	include_once( '../library/Index.php' );
	$page = new Index();
}
