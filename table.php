<?php

require_once("sqlqb1select.php");
require_once("html_controls/html_table.php");
require_once("html_controls/qb_page.php");

function select_columns_from_wheres_sorts($dbconn, $schema, $entity, $columns_array = array('*'), $where_array = array(), $sort_array = array()) {

	list($q, $p) = construct_select(
		$schema,
		$entity,
		$expressions = $columns_array,
		$contexts = array(),
		$rowids_fields = array(),
		$rowids_tuples = array(),
		$wheres = array(),
		$sorts = $sort_array
	);

	$rows = pg_query_params($dbconn, $q, $p);

	$all_rows = pg_fetch_all($rows);

	return $all_rows;

}

require_once("session.php");
require_once("helpers.php");
require_once("sqlqb1.php");

$mandatory = array('c', 'schema', 'entity');
$optional = array('sort', 'filter');
$my_qsparams = array_merge($common_qsparams, $mandatory, $optional);

$sort = array();
extract_select($_GET, $mandatory, $optional);

list($db, $entities, $entity_params, $entity_attributes) = get_deamix_for_entity($dbconn, $schema, $entity);
$dbname = $db[0]['pretty_name'];

$pkfields = get_pkfields($entity_attributes);
$entity_attributes = entity_attributes_add_controls($entity_attributes);

$expressions = array();
foreach ($entity_attributes as $k => $v) {
	$expressions[] = $v['select_expression'];
}
$wheres = array();
$columns = $expressions;

$entity_rows = select_columns_from_wheres_sorts($dbconn, $schema, $entity, $columns, $wheres, $sort);

if ($entity_rows == FALSE) {
	$entity_rows = array();
}

# TABLE PREPARATION
$table = new qb_actionable_data_grid($db[0]['identifier'], qs_array_prepare($_GET, $my_qsparams), 'sort');
if ($entity_params['can_select'])
    $table->add_action('View Details', '', '', 'view-record.php', array('schema' => $schema, 'entity' => $entity));
if ($entity_params['can_insert'])
    $table->add_action('Insert', '', '', 'action-insert-record.php');
if ($entity_params['can_insert'])
    $table->add_action('Duplicate', 'action', '___qb_duplicate');
if ($entity_params['can_update'])
    $table->add_action('Modify', '', '', 'edit-record.php', array('schema' => $schema, 'entity' => $entity));
if ($entity_params['can_delete'])
    $table->add_action('Delete', 'action', '___qb_delete');
foreach($entity_attributes as $colk => $col) {
    $table->add_data_column($col['pretty_name'], $colk);
}
foreach($entity_rows as $rk => $r) {
    $rowid_tuple = record_columns_to_rowid_tuple($r, $pkfields);
    $rowid_array = record_columns_to_rowid_array($r, $pkfields);
    $thisrowidq = http_build_query(array('rowid' => $rowid_array));
    foreach($entity_attributes as $colk => $col) {
		$entity_attributes[$colk]['control']->set_value_from_sql($r[$colk]);
		$entity_attributes[$colk]['control']->set_parameters("rowid=$thisrowidq");
        $r[$colk] = $entity_attributes[$colk]['control']->get_html_static("data[$thisrowidq][columns][$colk]");
    }
    $table->add_row($rowid_array, $r);
}
if(isset($_GET['sort']))
    $table->set_sort_state($_GET['sort']);
$table->add_qsparam('c', $c);
$table->add_qsparam('schema', $schema);
$table->add_qsparam('entity', $entity);
$table->set_action_handler('action.php');


require_once("sqlqb1.php");
require_once("helpers.php");

foreach($entities as $k => $e) {
	$entities[$k]['parameterstring'] = http_build_query(
		qs_array_prepare($_GET, $common_qsparams,
			array(
				'schema' => $e['schema'],
				'entity' => $e['identifier'],
			)
		)
	);
	$entities[$k]['nbsp_pretty_name'] = str_replace(' ','&nbsp;',$e['pretty_name']);
}

$p = new qb_widget_page($dbname, "Table view");

foreach($entities as $e) {
    $p->add_menu_link($e['nbsp_pretty_name'], "/table.php?${e['parameterstring']}", $e['description']);
}

$header_h2 = new qb_html_element('header', array('class' => 'entity'));
$header_h2->add_child(new qb_html_element('h2', array(), $entity_params['pretty_name'], True));
$p->add_child($header_h2);

$main = new qb_html_element('main', array('class' => "entity-interaction"));
$main->add_child($table);

$count = new qb_html_element('p', array(), count($entity_rows) . " " . strtolower($entity_params['pretty_name']));

$p->add_child($main);
$p->add_child($count);

print($p->get_html());

