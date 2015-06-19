<?php

class Utils {

	// html start
	public function htmlFragmentStart($title) {
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta http-equiv="Content-type" content="text/html; charset=utf-8">
			<title><?php echo($title); ?></title>
			<link rel="shortcut icon" type="image/ico" href="<?php echo(DEEPLIOURL); ?>assets/img/favicon.ico" />
			<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
			<style type="text/css" media="screen">
				h1 { color: #555; }
				h1 span { font-size: 0.6em; color: #999; }
				body { color: #666; }
			</style>
		</head>
		<body>

			<header class="navbar navbar-default" role="banner">
				<div class="container">
					<div class="navbar-header">
						<a href="<?php echo(DEEPLIOURL); ?>" class="navbar-brand">Deepl.io</a>
					</div>
					<nav class="collapse navbar-collapse" role="navigation">
						<!--<ul class="nav navbar-nav">
							<li>
								<a href="<?php echo(DEEPLIOURL); ?>">Overview</a>
							</li>
						</ul>-->
					</nav>
				</div>
			</header>

			<div class="container">
	<?php
	}

	// html end
	public function htmlFragmentEnd($script){

			echo '</div>';

			if (isset($script) && !empty($script)) {
				echo '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>';
				echo '<script type="text/javascript">';
				echo 'if (window.jQuery) {';
				echo $script;
				echo '}' ;
				echo '</script>';
			}
		echo '</body>';
		echo '</html>';
	}
}
