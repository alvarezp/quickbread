<?php

require_once("session.php");
require_once("helpers.php");
require_once("sqlqb1.php");
require_once("sqlqb1select.php");
require_once("html_controls/qb_page.php");
require_once("html_controls/html_list.php");

function select_rows($dbconn, $schema, $entity, $columns_array, $rowid_fields, $rowid_tuples) {

	list($q, $p) = construct_select(
		$schema,
		$entity,
		$columns_array,
		$contexts = array(),
		$rowid_fields,
		$rowid_tuples
	);

	$rows = pg_query_params($dbconn, $q, $p);

	$all_rows = pg_fetch_all($rows);

	return $all_rows;

}

$mandatory = array('c', 'schema', 'entity', 'rows');
$my_qsparams = array_merge($common_qsparams, $mandatory);

extract_select($_GET, $mandatory);

list($db, $entities, $entity_params, $entity_attributes) = get_deamix_for_entity($dbconn, $schema, $entity);
$dbname = $db[0]['pretty_name'];

$pkfields = get_pkfields($entity_attributes);
$entity_attributes = entity_attributes_add_controls($entity_attributes);

$expressions = array();
foreach ($entity_attributes as $k => $v) {
	$expressions[] = $v['select_expression'];
}

$parsed_rows = array();
foreach($rows as $k => $r) {
    parse_str($r, $parsed_rows[]);
}

$wheres = array();
$columns = $expressions;

$entity_rows = select_rows($dbconn, $schema, $entity, $expressions, $pkfields, $parsed_rows);

if ($entity_rows == FALSE) {
	$entity_rows = array();
}

$p = new qb_widget_page($dbname, "Record details");
foreach($entities as $k => $e) {
	$entities[$k]['parameterstring'] = http_build_query(
		qs_array_prepare($_GET, $common_qsparams,
			array(
				'schema' => $e['schema'],
				'entity' => $e['identifier'],
			)
		)
	);
	$nbsp_pretty_name = str_replace(' ','&nbsp;',$e['pretty_name']);
    $p->add_menu_link($nbsp_pretty_name, "/table.php?${e['parameterstring']}", $e['description']);
}

$header_h2 = new qb_html_element('header', array('class' => 'entity'));
$header_h2->add_child(new qb_html_element('h2', array(), "${entity_params['pretty_name']} details", True));
$p->add_child($header_h2);


$all_records_list = new qb_html_ulist();
foreach ($entity_rows as $rk => $r) {
    $this_record_list = new qb_html_ulist();

	foreach ($r as $key => $column) {
	    $entity_attributes[$key]['control']->set_value_from_sql($column);
        $rowid = http_build_query(array('rowid' => record_columns_to_rowid_array($r, $pkfields)));
	    $entity_attributes[$key]['control']->set_parameters("catalog=$c&schema=$schema&entity=$entity&field=$key&rowid=$rowid");
        $pretty_name = $entity_attributes[$key]['pretty_name'];
        $label = new qb_html_element('label', array('class' => "view", 'for' => "data[0][columns][$key]"), "$pretty_name:");
        $this_record_list->add_item($label->get_html() . $entity_attributes[$key]['control']->get_html_static("data[0][columns][$key]"));
    }

    $all_records_list->add_item($this_record_list);
}
$p->add_child($all_records_list);

print($p->get_html());

