<?php
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// Blocking direct access to plugin      -=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
defined('ABSPATH') or die('Are you crazy!');

// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

/**
 * Show date ISO in various format (Pixel Stat)
 * This method return string to display
 *
 * @param	$date		Date ISO Format from MySQL
 * @param	$format		Format to return (ISO, US, UST, FR, FRT, FRH, YEAR)
 *
 * @return converted string
 */
if (!function_exists("checked_convert_date")) {
	function checked_convert_date($date, $format = 'ISO') {
		switch(strtoupper($format)) {
			// Format ISO (AAAA-MM-DD)
			default: return strftime("%F", strtotime($date)); break;
			// Format US (MM-DD-AAAA)
			case "US": return strftime("%m/%d/%Y", strtotime($date)); break;
			// Format US (MM-DD-AAAA HH:mm:ss)
			case "UST": return strftime("%m/%d/%Y %T", strtotime($date)); break;
			// Format FR (DD-MM-AAAA)
			case "FR": return strftime("%d/%m/%Y", strtotime($date)); break;
			// Format FR (DD-MM-AAAA HH:mm:ss) with time
			case "FRT": return strftime("%d-%m-%Y %T", strtotime($date)); break;
			// Format FRH (DD MM AAAA) Human readable
			case "FRH": return strftime("%e %B %Y", strtotime($date)); break;
			// Year (AAAA)
			case "YEAR": return strftime("%Y", strtotime($date)); break;
		}
	}
}

/**
* Return a string to truncate
* @param string $string
* @param int $max
* @param string $replacement
* @return string truncated
*/
if (!function_exists("checked_truncate")) {
	function checked_truncate($string, $max = 20, $replacement = '') {
		if (strlen($string) <= $max) {
			return $string;
		}
		$leave = $max - strlen ($replacement);
		return substr_replace($string, $replacement, $leave);
	}
}

/**
* Searches text for unwanted tags and removes them
* @param string $text String to purify
* @return string $text The purified text
*/
if (!function_exists("checked_stopXSS")) {
	function checked_stopXSS($text) {
		if (!is_array($text)) {
			$text = preg_replace("/\(\)/si", "", $text);
			$text = strip_tags($text);
			$text = str_replace(array("\"",">","<","\\"), "", $text);
		} else {
			foreach($text as $k=>$t) {
				if (is_array($t)) {
					checked_StopXSS($t);
				} else {
					$t = preg_replace("/\(\)/si", "", $t);
					$t = strip_tags($t);
					$t = str_replace(array("\"",">","<","\\"), "", $t);
					$text[$k] = $t;
				}
			}
		}
		return $text;
	}
}

/**
 * Check whether URL is HTTPS/HTTP
 * @return boolean [description]
 */
if (!function_exists("checked_is_secure")) {
	function checked_is_secure() {
		if (
			( ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| ( ! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
			|| ( ! empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
			|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
			|| (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443)
			|| (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
		) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Returns a rewrite string
 * @return string
 */
if (!function_exists("checked_rewrite_string")) {
	function checked_rewrite_string($string = "") {
		$noValidString = trim($string);
		$noValidString = preg_replace('`\s+`', '-', trim($noValidString));
		$noValidString = str_replace("'", "-", $noValidString);
		$noValidString = str_replace('"', '-', $noValidString);
		$noValidString = preg_replace('`_+`', '-', trim($noValidString));
		$caracters_in  = array(' ', '?', '!', '.', ',', ':', "'", '&', '(', ')', '-', '/', '%', '=', '+', '[', ']', '~', '"', '{', '}', '|', "`", '@', '$', '£', '*');
		$caracters_out = array('-', '', '', '', '', '-', '-', '-', '', '', '-', '-', '-', '-', '-', '', '', '', '', '', '', '', '-', '-', '-', '-', '');
		$noValidString = str_replace($caracters_in, $caracters_out, $noValidString);
		$noValidString = str_replace("------", "-", $noValidString);
		$noValidString = str_replace("-----", "-", $noValidString);
		$noValidString = str_replace("----", "-", $noValidString);
		$noValidString = str_replace("---", "-", $noValidString);
		$noValidString = str_replace("--", "-", $noValidString);
		$accents       = array('À','Á','Â','Ã','Ä','Å','à','á','â','ã','ä','å','Ò','Ó','Ô','Õ','Ö','Ø','ò','ó','ô','õ','ö','ø','È','É','Ê','Ë','è','é','ê','ë','Ç','ç','Ì','Í','Î','Ï','ì','í','î','ï','Ù','Ú','Û','Ü','ù','ú','û','ü','ÿ','Ñ','ñ');
		$ssaccents     = array('A','A','A','A','A','A','a','a','a','a','a','a','O','O','O','O','O','O','o','o','o','o','o','o','E','E','E','E','e','e','e','e','C','c','I','I','I','I','i','i','i','i','U','U','U','U','u','u','u','u','y','N','n');
		$validString   = str_replace($accents, $ssaccents, $noValidString);

		return ($validString);
	}
}

/**
 * Check locale WP Site
 * @return LC_TIME (setlocale)
 */
if (!function_exists("am_get_locale")) {
	function am_get_locale() {
		$am_locale_wp = get_locale();
		if ($am_locale_wp == 'fr_FR')
			setlocale(LC_TIME, "fr_FR.utf8", "fra");
		else {
			setlocale(LC_TIME, "$am_locale_wp.utf8", "fra");
		}
	}
}

function am_random3($length = 4 ) {

	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {

		$bytes = openssl_random_pseudo_bytes( $length * 2 );

		if ( $bytes === false ) {
			throw new \RuntimeException( 'Unable to generate random string.' );
		}

		return substr( str_replace( array( '/', '+', '=' ), '', base64_encode( $bytes ) ), 0, $length );
	}

	return am_random4( $length );
}

function am_random4($length = 16 ) {

	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	return substr( str_shuffle( str_repeat( $pool, $length ) ), 0, $length );
}
