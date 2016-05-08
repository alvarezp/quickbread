<?php

function construct_select (
  $schema, $entity,
  $expressions = array(),
  $contexts = array(),
  $rowids_keys = array(),
  $rowids_tuples = array(),
  $wheres = array(),
  $sort = array()
) {

	$sqlwheres = array();
	$sqlparameters = array();

	$entity_attributes = array();
	$sqlexpressions = implode(", ", $expressions);

	# $contexts not yet implemented. Parameter ignored for now.

	# Because we will be using pg_query_params(), we will need to track
	# parameters.
	$placeholder_start = 1;

	# Convert $rowids to the form "(f1, f2, f3) IN (($1, $2, $3), ($4, $5, $6), ($7, $8, $9), ...)
	if (count($rowids_keys) > 0) {
		$rowids_filter = "(" .
			implode(") IN (",
				array(
					implode(", ", $rowids_keys),
					implode(", ", array_map(function($x, $s) {
						return "(" . implode(", ", array_map(function($x) { return "\$$x"; }, range($s, $s-1+count($x)))) . ")";
					}, $rowids_tuples,
						range($placeholder_start, $placeholder_start+count($rowids_keys)*(count($rowids_tuples)-1), count($rowids_keys))))
				))
			. ")";

		$placeholder_start += count($rowids_keys)*count($rowids_tuples);
        $sqlparameters = array();
		foreach ($rowids_tuples as $values) {
			foreach ($values as $val) {
				$sqlparameters[] = $val;
			}
		}
		$sqlwheres[] = $rowids_filter;
	}

	$sqlwheres = array_merge($sqlwheres, $wheres);
	$where = implode(" AND ", array_map(function($x) { return "(" . $x . ")"; }, $sqlwheres));
	if ($where != "")
		$where = " WHERE $where";

	$orderby = implode(", ", array_map(function($k) { return $k['field'] . " " . ($k['order'] == 1 ? 'ASC' : 'DESC'); }, $sort));
	if ($orderby != "")
		$orderby = " ORDER BY $orderby";

	return array("SELECT $sqlexpressions FROM $schema.${entity}${where}${orderby}", $sqlparameters);

}


## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;

    print("Test select-expression-from...\n");
    $e_query = "SELECT f1 FROM s.e";
    $e_params = array();
    list($r_query, $r_params) = construct_select("s", "e", array("f1"));
    if (!assert($r_query == $e_query))
        print "$r_query\n";
    if (!assert($r_params == $e_params))
        print "$r_params\n";

    print("Test select-expressions-from...\n");
    $e_query = "SELECT f1, f2 FROM s.e";
    $e_params = array();
    list($r_query, $r_params) = construct_select("s", "e", array("f1", "f2"));
    if (!assert($r_query == $e_query))
        print "$r_query\n";
    if (!assert($r_params == $e_params))
        print "$r_params\n";

    print("Test select-expressions-from-where-rowid-1k-1r...\n");
    $e_query = "SELECT f1, f2 FROM s.e WHERE ((f1) IN (($1)))";
    $e_params = array("x");
    list($r_query, $r_params) = construct_select("s", "e", array("f1", "f2"), array(), array("f1"), array(array('x')));
    if (!assert($r_query == $e_query))
        print "$r_query\n";
    if (!assert($r_params == $e_params))
        print "$r_params\n";

    print("Test select-expressions-from-where-rowid-2k-1r...\n");
    $e_query = "SELECT f1, f2 FROM s.e WHERE ((f1, f2) IN (($1, $2)))";
    $e_params = array("x", "y");
    list($r_query, $r_params) = construct_select("s", "e", array("f1", "f2"), array(), array("f1", "f2"), array(array('x', 'y')));
    if (!assert($r_query == $e_query))
        print "$r_query\n";
    if (!assert($r_params == $e_params))
        print "$r_params\n";

    print("Test select-expressions-from-where-rowid-2k-2r...\n");
    $e_query = "SELECT f1, f2 FROM s.e WHERE ((f1, f2) IN (($1, $2), ($3, $4)))";
    $e_params = array("x", "y", "a", "b");
    list($r_query, $r_params) = construct_select("s", "e", array("f1", "f2"), array(), array("f1", "f2"), array(array('x', 'y'), array('a', 'b')));
    if (!assert($r_query == $e_query))
        print "$r_query\n";
    if (!assert($r_params == $e_params))
        print "$r_params\n";

    print("Test select-expressions-from-where-rowid-2k-2r-plus-another-where...\n");
    $e_query = "SELECT f1, f2 FROM s.e WHERE ((f1, f2) IN (($1, $2), ($3, $4))) AND (1 == 1)";
    $e_params = array("x", "y", "a", "b");
    list($r_query, $r_params) = construct_select("s", "e", array("f1", "f2"), array(), array("f1", "f2"), array(array('x', 'y'), array('a', 'b')), array("1 == 1"));
    if (!assert($r_query == $e_query))
        print "$r_query\n";
    if (!assert($r_params == $e_params))
        print "$r_params\n";

    print ("Test 1\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "\"thetable\"",
	    $expressions = array("a", "b")
    );
    assert($query == "SELECT a, b FROM theschema.\"thetable\"");
    assert($parameters == array());

    print ("Test 2\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "thetable",
	    $expressions = array("a", "b"),
	    $contexts = array(),
	    $rowids_fields = array("a"),
	    $rowids_tuples = array(array(4), array(3))
    );
    assert($query == "SELECT a, b FROM theschema.thetable WHERE ((a) IN (($1), ($2)))");
    assert($parameters == array(4, 3));

    print ("Test 3\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "thetable",
	    $expressions = array("a", "b"),
	    $contexts = array(),
	    $rowids_fields = array("a", "b"),
	    $rowids_tuples = array(array(4, 7), array(3, 9))
    );
    assert($query == "SELECT a, b FROM theschema.thetable WHERE ((a, b) IN (($1, $2), ($3, $4)))");
    assert($parameters == array(4, 7, 3, 9));

    print ("Test 4\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "thetable",
	    $expressions = array("a", "b"),
	    $contexts = array(),
	    $rowids_fields = array("a", "b"),
	    $rowids_tuples = array(array(4, 7), array(3, 9)),
	    $wheres = array("a = b")
    );
    assert($query == "SELECT a, b FROM theschema.thetable WHERE ((a, b) IN (($1, $2), ($3, $4))) AND (a = b)");
    assert($parameters == array(4, 7, 3, 9));

    print ("Test 5\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "thetable",
	    $expressions = array("a", "b"),
	    $contexts = array(),
	    $rowids_fields = array("a", "b"),
	    $rowids_tuples = array(array(4, 7), array(3, 9)),
	    $wheres = array("a = b", "c = d")
    );
    assert($query == "SELECT a, b FROM theschema.thetable WHERE ((a, b) IN (($1, $2), ($3, $4))) AND (a = b) AND (c = d)");
    assert($parameters == array(4, 7, 3, 9));

    print ("Test 6\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "thetable",
	    $expressions = array("a", "b"),
	    $contexts = array(),
	    $rowids_fields = array("a", "b"),
	    $rowids_tuples = array(array(4, 7), array(3, 9)),
	    $wheres = array("a = b", "c = d"),
	    $sort = array(array('field' => 'a', 'order' => 1))
    );
    assert($query == "SELECT a, b FROM theschema.thetable WHERE ((a, b) IN (($1, $2), ($3, $4))) AND (a = b) AND (c = d) ORDER BY a ASC");
    assert($parameters == array(4, 7, 3, 9));

    print ("Test 7\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "thetable",
	    $expressions = array("a", "b"),
	    $contexts = array(),
	    $rowids_fields = array("a", "b"),
	    $rowids_tuples = array(array(4, 7), array(3, 9)),
	    $wheres = array("a = b", "c = d"),
	    $sort = array(array('field' => 'a', 'order' => 1), array('field' => 'b', 'order' => -1))
    );
    assert($query == "SELECT a, b FROM theschema.thetable WHERE ((a, b) IN (($1, $2), ($3, $4))) AND (a = b) AND (c = d) ORDER BY a ASC, b DESC");
    assert($parameters == array(4, 7, 3, 9));

    print ("Test 8\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "thetable",
	    $expressions = array("a", "b"),
	    $contexts = array(),
	    $rowids_fields = array("a", "b", "c"),
	    $rowids_tuples = array(array(4, 7, 9), array(3, 9, 10)),
	    $wheres = array("a = b", "c = d"),
	    $sort = array(array('field' => 'a', 'order' => 1), array('field' => 'b', 'order' => -1))
    );
    assert($query == "SELECT a, b FROM theschema.thetable WHERE ((a, b, c) IN (($1, $2, $3), ($4, $5, $6))) AND (a = b) AND (c = d) ORDER BY a ASC, b DESC");
    assert($parameters == array(4, 7, 9, 3, 9, 10));

    print ("Test 9\n");
    list($query, $parameters) = construct_select(
	    $schema = "theschema",
	    $entity = "thetable",
	    $expressions = array("a", "b"),
	    $contexts = array(),
	    $rowids_fields = array("a", "b", "c"),
	    $rowids_tuples = array(array(4, 7, 9)),
	    $wheres = array("a = b", "c = d"),
	    $sort = array(array('field' => 'a', 'order' => 1), array('field' => 'b', 'order' => -1))
    );
    assert($query == "SELECT a, b FROM theschema.thetable WHERE ((a, b, c) IN (($1, $2, $3))) AND (a = b) AND (c = d) ORDER BY a ASC, b DESC");
    assert($parameters == array(4, 7, 9));

