<?php

# Escapes ' and \ for \' and \\ and single-quotes the result.
# See: http://php.net/manual/en/function.pg-connect.php
function pg_sanitize_connstr_values($v) {
	return "'" . preg_replace('/([\'\\\\])/', "\{1}", $v) . "'";
}

function kequalsv($k, $v) {
	return "$k=$v";
}

function pg_connection_string($user, $password, $host, $port, $dbname) {
	$keys = array('user', 'password', 'host', 'port', 'dbname');
	$rawinput = array($user, $password, $host, $port, $dbname);
	$saneinput = array_map("pg_sanitize_connstr_values", $rawinput);
	return implode(' ', array_map("kequalsv", $keys, $saneinput));
}

function pg_fetch_all_array($dbconn, $query, $params = array()) {
		$return_set = array();
		$result = pg_query_params($dbconn, $query, $params);
		while ($row = pg_fetch_array($result))
			$return_set[] = $row;
		return $return_set;
}

function pg_fetch_value($dbconn, $query, $params = array()) {
		$array = pg_fetch_all_array($dbconn, $query, $params);
		return $array[0][0];
}


?>
