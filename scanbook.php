<?php
//specify your own timezone, this tells PHP to use UTC.
date_default_timezone_set( 'UTC' );
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
session_start();

include_once("RelativePath.php");
include_once("ePubReader.php");

$eReader = new ePubReader();

?>

