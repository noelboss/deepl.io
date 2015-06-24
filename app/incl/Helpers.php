<?php
namespace noelbosscom;

class Helpers {

	/**
	 * Grabs the mails from config or returns false
	 *
	 * @return string
	 * @author Noël Bossart
	 */
	public function getMails($conf) {
		$nomail = false;
		$to = '';

		if(!isset($conf->mails)){
			$nomail = true;
		} else {
			$mails = (array) $conf->mails;

			if(count($mails) > 0){
				foreach ($mails as $mail => $name) {
					if(filter_var($mail, FILTER_VALIDATE_EMAIL)){
						$to .= "$name <$mail>,";
					}
				}
			}
		}

		// final check if we got mail addresses...
		if(strpos($to,'@') === false || $nomail){
			return false;
		}
		$to = substr($to, 0,-1);
		return $to;
	}
}