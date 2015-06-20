<?php

	/* you can access functions from deepl.io since you run in its context: */
	$this->log('Running PHP Deploy Script...');
	// Commands run from the root directory of deepl.io
	// file_put_contents('./testfile.txt', "Hello World", FILE_APPEND);

	/* run your commands here. PHP takes precedence over SH scripts sinces
	you can also call the shell script if you need to from here: /
		exec($path.'.script.sh', $out, $ret);
		if ($ret){
			$this->log('Error: Error executing command in '.$path.'.script.sh:');
			$this->log("   return code $ret");
		} else {
			// whatever
		}
	/**/


?>
