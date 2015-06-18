<?php
namespace noelboss.com\Deeploy;
// -------------------------------------------------------------------------------------------
// Deeploi 0.0.1
// https://github.com/noelboss/deeploi
//
// Do not change this file - use config/customisation.php for your customisations
// -------------------------------------------------------------------------------------------

	define( 'BASE', __DIR__ . '/' );

	class Deeploi {
		private $config; = json_decode( file_get_contents( BASE . 'config/config.json' ) );
		/*
		$data = file_get_contents('php://input');
		$hook->run(json_decode($data)); */

		public function __construct($arg1, $arg2) {
			var_dump(
				$this->config;
			);
		}
	}


	if(file_exists(BASE . 'config/customisation.php')){
		include_once( BASE . 'config/customisation.php' );
	}
