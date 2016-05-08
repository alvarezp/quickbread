<?php

require_once("pghelpers.php");

function get_database_prettyname($dbconn) {

	return pg_fetch_value($dbconn, "SELECT pretty_name FROM ___qb1.database;");

}

function get_database_and_entity_list($dbconn) {

	$query = "SELECT * FROM ___qb1.deamix WHERE object_type IN ('D', 'E')";

	$database = array();
	$entity_list = array();

	$result = pg_query($dbconn, $query);
	while ($row = pg_fetch_array($result)) {
		if ($row['object_type'] == 'D') {
			$database[] = $row;
		} elseif ($row['object_type'] == 'E') {
			$entity_list[] = $row;
		}
	}

	return array($database, $entity_list);

}

function sanitize($str) {
	return "'" . preg_replace('/(\')/', "''", $str) . "'";
}

function get_deamix_for_entity($dbconn, $schema, $entity) {

	$query = "SELECT * FROM ___qb1.deamix
		WHERE
			object_type IN ('D', 'E')
			OR (object_type IN ('A')
				AND schema = " . sanitize($schema) . "
				AND entity_identifier = " . sanitize($entity) . ")";

	$database = array();
	$entity_list = array();
    $entity_attributes = array();

	$result = pg_query($dbconn, $query);
	while ($row = pg_fetch_array($result)) {
		if ($row['object_type'] == 'D') {
			$database[] = $row;
		} elseif ($row['object_type'] == 'E') {
			$entity_list[] = $row;
			if ($row['schema'] == $schema && $row['identifier'] == $entity) {
				$entity_params = $row;
			}
		} else {
			$entity_attributes[$row['name']] = $row;
		}
	}

	return array($database, $entity_list, $entity_params, $entity_attributes);

}

function get_entity_list($dbconn) {

	$entities_query = 'SELECT schema, name, identifier, description, pretty_name, recent_interaction FROM ___qb1.entities;';

	return pg_fetch_all_array($dbconn, $entities_query);

}

function get_attribute_list_for_table($dbconn, $schema, $table) {

	$entities_query = 'SELECT * FROM ___qb1.attributes;';

	return pg_fetch_all_array($dbconn, $entities_query);

}

function get_entity_entry($dbconn, $schema, $entity) {

	$entities_query = 'SELECT * FROM ___qb1.entities WHERE schema = $1 AND identifier = $2;';

	return pg_fetch_all_array($dbconn, $entities_query, array($schema, $entity))[0];

}

function get_entity_attribute_list($dbconn, $schema, $entity) {

	$entity_attributes_query = 'SELECT * FROM ___qb1.entity_attributes WHERE schema = $1 AND entity_identifier = $2 ORDER BY ordinal_position;';

	$retval = array();

	$rows = pg_fetch_all_array($dbconn, $entity_attributes_query, array($schema, $entity));

	foreach($rows as $r) {
		$retval[$r['name']] = $r;
	}

	return $retval;
}

function get_all_column_expressions_for_entity($dbconn, $schema, $entity) {

	$entity_attributes_query = 'SELECT select_expression FROM ___qb1.entity_attributes WHERE schema = $1 AND entity = $2 ORDER BY ordinal_position;';

	$retval = array();

	$rows = pg_fetch_all_array($dbconn, $entity_attributes_query, array($schema, $entity));

	foreach($rows as $r) {
		$retval[$r['name']] = $r;
	}

	return $retval;
}

function select_columns_from_rowid($dbconn, $schema, $entity, $columns_array, $rowid) {

	if ($columns_array == array('*')) {
		$columns_array = get_all_column_expressions_for_entity($dbconn, $schema, $entity);
	}

	$columns = implode(", ", $columns_array);

	$where = implode(" AND ", array_map(function($x) { return "(" . $x . ")"; }, $rowid));
	if ($where != "")
		$where = "WHERE $where";

	$rows = pg_query_params($dbconn, "SELECT $columns FROM \"$schema\".\"$table\" $where;", array());

	$all_rows = pg_fetch_all($rows);

	return $all_rows;

}

function get_pkfields($entity_attributes) {
	$pkfields = array();

	foreach($entity_attributes as $a) {
		if ($a['pk_name'] != "") {
			$pkfields[] = $a['name'];
		}
	}
	if (count($pkfields) > 0)
		return $pkfields;

	# If no primary key at all, use all columns as primary key.
	foreach($entity_attributes as $a) {
		$pkfields[] = $a['name'];
	}

	return $pkfields;
}

function record_columns_to_rowid_array($r, $pkfields) {

	$rowid_array = array();

	foreach($r as $col_name => $d) {
		if (in_array($col_name, $pkfields))
			$rowid_array[$col_name] = $d;
	}

	return $rowid_array;
}

function record_columns_to_rowid_tuple($r, $pkfields) {

	$rowid_array = array();

	foreach($r as $col_name => $d) {
		if (in_array($col_name, $pkfields))
			$rowid_array[] = $d;
	}

	return $rowid_array;
}

?>
