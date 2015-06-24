<?php
namespace noelbosscom;

/* send test request */
class Test {
	private $config;
	public $response;

	function __construct($conf) {

		$this->config = $conf;

		// sending testdata...
		$options = array(
			'http' => array(
				'method'=> 'POST',
				'content' => json_encode( json_decode($_POST['req']) ),
				'header'=>"Content-Type: application/json\r\n" .
					"Accept: application/json\r\n"
				)
		);

		$url = 'http://'.$_SERVER["HTTP_HOST"].'/'.$this->config->security->token;

		$context = stream_context_create( $options );
		$response = file_get_contents( $url, false, $context );

		$class = 'info';
		$class = strripos($response, 'success') !== false ? 'success' : $class;
		$class = strripos($response, 'warning') !== false ? 'warning' : $class;
		$class = strripos($response, 'error') !== false ? 'danger' : $class;
		$this->response ='<div class="alert alert-'.$class.'" role="alert">
			<h4>Request sent:</h4>
			<pre>'.$response.'</pre>
		</div>';
	}
}
