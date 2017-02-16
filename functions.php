<?php
/*  
 $absolute_url = full_url($_SERVER);
 echo $absolute_url;
 */
	function pk($data) {
		return urlencode(serialize($data));
	}

	function unpk($data) {
		return unserialize(urldecode($data));
	}

	function curPageURL() {
		$isHTTPS = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on");
		$port = (isset($_SERVER["SERVER_PORT"]) && ((!$isHTTPS && $_SERVER["SERVER_PORT"] != "80") || ($isHTTPS && $_SERVER["SERVER_PORT"] != "443")));
		$port = ($port) ? ':'.$_SERVER["SERVER_PORT"] : '';
		$url = ($isHTTPS ? 'https://' : 'http://').$_SERVER["SERVER_NAME"].$port.$_SERVER["REQUEST_URI"];
		return $url;
	}


	function getBaseUrl($replace = "index.php"){
		$isHTTPS = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on");
		$wport = (isset($_SERVER["SERVER_PORT"]) && ((!$isHTTPS && $_SERVER["SERVER_PORT"] != "80") || ($isHTTPS && $_SERVER["SERVER_PORT"] != "443")));
		$port = ($wport) ? ':'.$_SERVER["SERVER_PORT"] : '';
		$domain = ($isHTTPS ? 'https://' : 'http://').$_SERVER["SERVER_NAME"]. $port . $_SERVER["SCRIPT_NAME"];
        if($replace == FALSE){
            return $domain;
        }
		return str_replace($replace, NULL, $domain);
	}
	
	function siteUrl(){
		$requri = str_replace('index.php', NULL, $_SERVER['PHP_SELF']);
		$isHTTPS = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		$port = (isset($_SERVER['SERVER_PORT']) && ((!$isHTTPS && $_SERVER['SERVER_PORT'] != "80") || ($isHTTPS && $_SERVER['SERVER_PORT'] != "443")));
		$port = ($port) ? ':'.$_SERVER['SERVER_PORT'] : '';
		$siteURL = ($isHTTPS ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].$port.$requri;
		return $siteURL;
	}
?>