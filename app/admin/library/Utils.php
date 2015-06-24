<?php
namespace noelbosscom;

class Utils {

	// html start
	public function htmlFragmentStart($title) {
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta http-equiv="Content-type" content="text/html; charset=utf-8">

			<title><?php echo($title); ?></title>
			<meta name="description" content="A simple yet powerful php-tool to catch GIT Web-Hooks from GITLAB and GITHUB to deploy your projects to your server after pushing." />

			<link rel="shortcut icon" href="/assets/img/icon/favicon.png" type="image/x-icon" />
			<link rel="apple-touch-icon" href="/assets/img/icon/apple-touch-icon.png" />
			<meta name="application-name" content="Deepl.io" />
			<meta name="msapplication-TileColor" content="#5133ab" />
			<meta name="msapplication-TileImage" content="/assets/img/icon/tile-icon.png" />

			<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
			<style type="text/css" media="screen">
				h1 { color: #555; }
				h1 span { font-size: 0.6em; color: #999; }
				body { color: #666; }
				a { color: #E90089; }

				.btn-success { background: #79D195; }
				.btn-success:hover { background: #679C6A; }
				.btn-info { background: #E90089; }
				.btn-info:hover { background: #9A2160; }
				.btn, .btn:hover { border-color: #eee;}

				.logo { margin-left: -20px;}
				.navbar-header { line-height: 48px; }
				.logo-footer { margin: 6em 0 2em;}
				copy { color: #999; font-size: 0.9em; }
				copy a { color: #777; }
			</style>
		</head>
		<body>

			<header class="navbar navbar-default" role="banner">
				<div class="container">
					<div class="navbar-header">
						<a href="http://deepl.io" clasS="logo">
							<img src="/assets/img/deeplio-logo-claim@3x.png" height="48px" alt="Deeplio Logo">
						</a>
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

	public function htmlFooter(){
		?>
		<footer>
			<p class="text-center logo-footer">
				<a href="http://deepl.io">
					<img src="/assets/img/deeplio-logo@3x.png" height="25" alt="Deeplio Logo">
				</a>
			</p>
			<p class="text-right">
				<copy>© <?= date('Y')?> <a href="//noelboss.com">Noël Bossart</a>. Made in Switzerland.</copy>
			</p>
		</footer>
		<?php
	}


	// html end
	public function htmlFragmentEnd($script){
			echo '</div>';
			if (isset($script) && !empty($script)) {
				?>
				<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
				<script type="text/javascript">
				if (window.jQuery) {
					<?= $script ?>
				}
				</script>
				<?php
			}
		echo '</body>';
		echo '</html>';
	}
}
