<?php

require_once("helpers.php");

session_start();
session_destroy();
session_start();

if (!isset($user))
	$user = "";

if (!isset($dbname))
	$dbname = "";

if (!isset($socket))
	$socket = "";

if (!isset($urljump))
	$urljump = "";

if (!array_key_exists('dbname', $_GET))
	$_GET['dbname'] = "";

if (!array_key_exists('socket', $_GET))
	$_GET['socket'] = "";

if (!array_key_exists('urljump', $_GET))
	$_GET['urljump'] = "";

if (!isset($errors))
	$errors = array();

$urljump = $urljump ?: safe($_GET['urljump']) ?: "";
$dbname = $dbname ?: safe($_GET['dbname']) ?: "";
$welcome = prettify($dbname) ?: "Welcome!";
$socket = $socket ?: safe($_GET['socket']) ?: "";

?>

<!DOCTYPE html>

<html>

<head>
	<link rel="stylesheet" href="default_style.css" type="text/css">
	<meta http-equiv="Content-type" content="text/html; charset=utf8">
	<title><?= $welcome ?></title>
</head>

<body>

<div id="titlebar">
	<div id="title"><?= $welcome ?></div>
</div>

<?php if (count($errors) > 0): ?>
<div class="errors">
	<?php foreach($errors as $e): ?>
	<p><?= $e ?></p>
	<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="index header" id="index_header">
	<form class="index login" id="login_form" action="login.php" method="post">
		<table>
			<tr><td><label class="login" for="user">Username:</label></td><td><input id="user" class="login" type="text" name="user" value="<?= $user ?>"></td></tr>
			<tr><td><label class="login" for="password">Password:</label></td><td><input id="password" class="login" type="password" name="password"></td></tr>
			<tr><td><label class="login" for="dbname">Database:</label></td><td><input id="dbname" class="login" type="text" name="dbname" value="<?= $dbname ?>"></td></tr>
			<tr><td><label class="login" for="socket">Host:</label></td><td><input id="socket" class="login" type="text" name="socket" value="<?= $socket ?>"></td></tr>
		</table>

<?php if ($urljump): ?>
		<input class="login" type="hidden" name="urljump" value="<?= $urljump ?>">
<?php endif; ?>

		<p>
			<input class="login" type="submit" value="Login">
		</p>
	</form>
</div>

<div class="index footer" id="index_footer">
</div>

</body>
</html>

