<?php
//error_reporting(-1);
//ini_set('display_errors', '1');

$pathes  = explode( '/app/admin/public', dirname( str_replace( '\\', '/', __FILE__ ) ) );
$uri     = 'http' . ( empty( $_SERVER['HTTPS'] ) ? '' : 's' ) . '://' . $_SERVER['SERVER_NAME'] . ( $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '' ) . $_SERVER['REQUEST_URI'];
$baseuri = substr( $uri, 0, strrpos( $uri, '/deeplio/', - 1 ) );

define( 'BASE', $pathes[0] . '/' );
define( 'BASEURL', $baseuri . '/' );
define( 'DEEPLIOURL', $baseuri . '/admin/' );

include_once( '../library/Index.php' );
$page = new Index();
