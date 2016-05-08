<?php

require_once("session.php");
require_once("sqlqb1.php");
require_once("html_controls/qb_page.php");

list($db, $entities) = get_database_and_entity_list($dbconn);
$dbname = $db[0]['pretty_name'];

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

echo $p->get_html();
