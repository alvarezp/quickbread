<?php

require_once("helpers.php");
require_once("pghelpers.php");

session_start();

# Login is handled as a special case here.
if (isset($login) && $login === TRUE) {
	$_SESSION['user'] = $user;
	$_SESSION['password'] = $password;
}

# Where will each connection_string parameter be taken from:
# host: from the query string (s unless h or p already provided)
# port: from the query string (s unless h or p already provided)
# user: from the session
# password: from the session
# dbname: from the query string


# Set host and port
$host = "localhost";
$port = "5432";

if (isset($_GET['h'])) {
	$host = $_GET['h'];
}

if (isset($_GET['p'])) {
	$port = $_GET['p'];
}

if (!isset($_GET['h']) && !isset($_GET['p']) && isset($_GET['s'])) {
	$parsed_socket = parse_socket($socket);

	if ($parsed_socket['host'] != "")
		$host = $parsed_socket['host'];

	if ($parsed_socket['port'] != "")
		$port = $parsed_socket['port'];
}

# Set dbname
$dbname = $_SESSION['user'];
if (isset($_GET['c']))
	$dbname = $_GET['c'];

# If the session expired...
if (!isset($_SESSION['password']) || !isset($_SESSION['user'])) {

	# We must redirect to index.php with the best possible query string
	# so the user just has to retype his user and password.
	#
	# Also, a relogin_url is nice so the user can land right back to the
	# page he was trying to get in the first place.
	$new_qs = array();
	$new_qs['socket'] = build_socket($host, $port);
	$new_qs['database'] = $dbname;
	if (isset($dbname))
		$new_qs['dbname'] = $dbname;
	$new_qs['urljump'] = urlencode($_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));

	header('Location: /index.php?' . http_build_query($new_qs));
	exit;
}

# Recover user and password.
$user = $_SESSION['user'];
$password = $_SESSION['password'];

# Finally! Compose the connection_string...
$pgconnstr = pg_connection_string($user, $password, $host, $port, $dbname);

# ... and connect. Unfortunately, pg_connect does not cooperate with
# pg_last_error() so we have to do it ourself. Because I'm such a wuss with
# exceptions, I had to shamelessly recourse to copy+paste from StackOverflow.
#
# I should really wrap this into a function to unclutter it up.
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

try {
	$dbconn = @pg_connect($pgconnstr);
} catch (exception $e) {
	$error_message = $e->getMessage();
	$dbconn = FALSE;
}

set_error_handler(NULL);

if ($dbconn === FALSE) {
	array_push($errors, "Error while connecting to the database. Please double check your username, password and catalog name.");
	session_destroy();
	include("index.php");
	exit;
}

$_SESSION['user'] = $user;
$_SESSION['password'] = $password;

?>

