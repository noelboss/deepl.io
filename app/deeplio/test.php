<?php


	if(isset($_GET['config'])){
		$conffile = __DIR__.'/../../config/config.json';
		if(file_exists( $conffile )){
			$conf = json_decode( file_get_contents( $conffile ));
			if(is_object($conf)){
				if(!isset($conf->security->token) || strlen($conf->security->token) < 1) {
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
					echo "Please provide security token";
				} else {
					echo "Found and well formated.";
				}
			} else {
				header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
				echo "Not well formated!";
			}
		} else {
			echo "Not found!";
		}
	} else {
		echo "Working.";
	}
?>