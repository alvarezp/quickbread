<?php

require_once("helpers.php");

$errors = array();

if (!isset($_POST['user']))
	$_POST['user'] = "";

if (!isset($_POST['password']))
	$_POST['password'] = "";

if (!isset($_POST['socket']))
	$_POST['socket'] = "";

if (!isset($_POST['dbname']))
	$_POST['dbname'] = "";

if (!isset($_POST['welcome']))
	$_POST['welcome'] = "";

if (!isset($_POST['urljump']))
	$_POST['urljump'] = "";

$welcome = safe($_POST['welcome']) ?: "";
$user = safe($_POST['user']) ?: "";
$password = safe($_POST['password']) ?: "";
$dbname = safe($_POST['dbname']) ?: "";
$socket = safe($_POST['socket']) ?: "";
$urljump = safe($_POST['urljump']) ?: "";

if ($user == "") {
	array_push($errors, "You must provide me with your database username.");
}

if ($password == "") {
	array_push($errors, "You must provide me with your database password.");
}

$parsed_socket = parse_socket($socket);

$host = "localhost";
if ($parsed_socket['host'] != "") {
	$host = $parsed_socket['host'];
}
$port = 5432;
if ($parsed_socket['port'] != "") {
	$port = $parsed_socket['port'];
}

if (count($errors) > 0) {
	session_destroy();
	include("index.php");
	exit;
}

if ($dbname == "") {
	$dbname = $user;
}

# Login is handled as a special case in session.php. Also, we must match
# these variables with the typical case.
$login = TRUE;
$_GET['h'] = $host;
$_GET['p'] = $port;
$_GET['c'] = $dbname;
require_once("session.php");

# Once the PHP session is established, jump to the requested location...
if ($urljump) {
	header("Location: " . urlencode($urljump));
}

# ... or default to a good landing place.
$new_qs = array();

if (!in_array($host, array('localhost')))
	$new_qs['h'] = $host;

if ($port != '5432')
	$new_qs['p'] = $port;

$new_qs['c'] = $dbname;

header("Location: mainmenu.php?" . http_build_query($new_qs));

?>

