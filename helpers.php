<?php

$common_qsparams = array('h', 'p', 'c');

function sort_toggle_field($sort, $field) {

	if (!is_array($sort)) {
		$sort = array();
	}

	# If this field already was the most recently clicked, toggle ASC/DESC.
	if (count($sort) > 0) {
		if ($sort[0]['field'] == $field) {
			$sort[0]['order'] *= -1;
			return $sort;
		}
	}

	# If this field has previously selected, clear it. The next case
	# will prepend ASC at the start.
	foreach($sort as $k => $s) {
		if ($s['field'] == $field) {
			unset($sort[$k]);
			break;
		}
	}

	# Not previously chosen? Just add it with ASC at the start.
	array_unshift($sort, array('field' => $field, 'order' => 1));
	return $sort;

}

function prettify($rawname) {
	return preg_replace("/_/", " ", ucfirst($rawname));
}

# Example:
# If $originalqs = array("a" => "A", "b" => "B", "c" => "C"),
#    $indexes_inherited = array("a", "b") and
#    $extra_array = array("d" => "D", "b" => "Z"), then
# 
# qs_array_prepare($originalqs, $indexes_inherited, $extra_array)
#
# would return array("a" => "A", "b" => "Z", "d" => "D").
function qs_array_prepare($originalqs, $indexes_inherited, $extra_array = array()) {
	$ret = array();
	foreach($indexes_inherited as $index) {
		if (isset($originalqs[$index]))
			$ret[$index] = $originalqs[$index];
	}
	return $extra_array + $ret;
}

# Returns FALSE if not all indexes in array($mand) are in $entity_attributes.
# Else, it does something like extract() for $entity_attributes but only for those
# indexes specified in $mand and $opt.
function extract_select($entity_attributes, $mand, $opt = array()) {
    foreach($mand as $m) {
        if (!isset($entity_attributes[$m]))
            return FALSE;
    }
    foreach($mand as $m) {
        global $$m;
        $$m = $entity_attributes[$m];
    }
    foreach($opt as $o) {
        global $$o;
        if (isset($entity_attributes[$o]))
	        $$o = $entity_attributes[$o];
    }
    return TRUE;
}

### TEST FOR extract_select()
#printf ("%1s %1s %1s %1s %1s\n", $a, $b, $c, $d, $e);
#$x = array('a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E');
#$m = array('b', 'd');
#$o = array('e', 'a');
#$r = x($x, $m, $o);
#printf ("Result should be 1: "); print_r($r); print("\n");
#printf ("%1s %1s %1s %1s %1s\n", $a, $b, $c, $d, $e);
#$m = array('b', 'f');
#$r = x($x, $m, $o);
#printf ("Result should be 1: "); print_r(!$r); print("\n");
#printf ("%1s %1s %1s %1s %1s\n", $a, $b, $c, $d, $e);

function get_array_for_basic_query($get) {
	$ret = array();
	if (isset($get['h']))
		$ret['h'] = $get['h'];

	if (isset($get['p']))
		$ret['p'] = $get['p'];

	if (isset($get['c']))
		$ret['c'] = $get['c'];

	return $ret;
}

function valornull($var) {
	if ($var === NULL)
		return NULL;

	return $var;
}

function safe($string) {
	if ($string === NULL)
		return NULL;

	return htmlspecialchars($string, ENT_QUOTES|ENT_HTML5);
}

function parse_socket($socket) {

	$retval = array('host' => "", 'port' => "");

	// If NULL, just return.
	if ($socket === NULL) {
		return $retval;
	}

	// If empty, just return.
	if ($socket == "") {
		return $retval;
	}

	// If host part is bracket-enclosed...
	if (substr($socket, 0, 1) == '[') {
		$end = strpos($socket, ']', 1);
		$retval['host'] = substr($socket, 1, $end-1);
		if (substr($socket, $end+1, 1) == ":") {
			$retval['port'] = substr($socket, $end+2);
		}
		return $retval;
	}

	// or, if it is IPv4...
	if (strpos($socket, ".")) {
		$colon = strpos($socket, ":");
		if ($colon > 0) {
			// IPv4 host with port after colon
			$retval['host'] = substr($socket, 0, $colon);
			$retval['port'] = substr($socket, $colon+1);
			return $retval;
		}
		// IPv4 host with no port specified
		$retval['host'] = $socket;
		return $retval;
	}

	// or else, take it as host-only.
	$retval['host'] = $socket;
	return $retval;
}

function build_socket($host = "localhost", $port = "5432") {
	if ($port == "5432" && $host == "localhost")
		return "";

	if ($port != "5432" && strpos($host, ":") === FALSE)
		return $host . ":" . $port;

	if ($port != "5432" && strpos($host, ":") >= 0)
		return "[" . $host . "]:" . $port;

	return $host;

}

function entity_attributes_add_controls($entity_attributes) {
	foreach($entity_attributes as $k => $attribute) {
		if ($attribute['base_type'] == "boolean") {
			require_once("html_controls/html_control_textbox.php");
			$entity_attributes[$k]['control'] = new HtmlControlTextbox;
		} else if ($attribute['base_type'] == "character varying") {
			require_once("html_controls/html_control_textbox.php");
			$entity_attributes[$k]['control'] = new HtmlControlTextbox;
		} else if ($attribute['base_type'] == "date") {
			require_once("html_controls/html_control_calendar.php");
			$entity_attributes[$k]['control'] = new HtmlControlCalendar;
		} else if ($attribute['base_type'] == "text") {
			require_once("html_controls/html_control_textarea.php");
			$entity_attributes[$k]['control'] = new HtmlControlTextarea;
		} else if ($attribute['base_type'] == "xml") {
			require_once("html_controls/html_control_file.php");
			$entity_attributes[$k]['control'] = new HtmlControlFile;
		} else if ($attribute['base_type'] == "bytea") {
			require_once("html_controls/html_control_file.php");
			$entity_attributes[$k]['control'] = new HtmlControlFile;
		} else if ($attribute['base_type'] == "USER-DEFINED" && $attribute['data_type_name'] == "cryptmd5") {
			require_once("html_controls/html_control_password.php");
			$entity_attributes[$k]['control'] = new HtmlControlPassword;
		} else {
			require_once("html_controls/html_control_textbox.php");
			$entity_attributes[$k]['control'] = new HtmlControlTextbox;
		}
	}
	return $entity_attributes;
}


## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;
    
    assert_options(ASSERT_BAIL, TRUE);

    $s = sort_toggle_field(NULL, 'a');
    assert($s == array(array('field' => 'a', 'order' => 1)));
    $s = sort_toggle_field($s, 'a');
    assert($s == array(array('field' => 'a', 'order' => -1)));
    $s = sort_toggle_field($s, 'a');
    assert($s == array(array('field' => 'a', 'order' => 1)));
    $s = sort_toggle_field($s, 'b');
    assert($s == array(array('field' => 'b', 'order' => 1), array('field' => 'a', 'order' => 1)));
    $s = sort_toggle_field($s, 'b');
    assert($s == array(array('field' => 'b', 'order' => -1), array('field' => 'a', 'order' => 1)));
    $s = sort_toggle_field($s, 'a');
    assert($s == array(array('field' => 'a', 'order' => 1), array('field' => 'b', 'order' => -1)));
    $s = sort_toggle_field($s, 'c');
    assert($s == array(array('field' => 'c', 'order' => 1), array('field' => 'a', 'order' => 1), array('field' => 'b', 'order' => -1)));
    $s = sort_toggle_field($s, 'b');
    assert($s == array(array('field' => 'b', 'order' => 1), array('field' => 'c', 'order' => 1), array('field' => 'a', 'order' => 1)));
    $s = sort_toggle_field($s, 'c');
    assert($s == array(array('field' => 'c', 'order' => 1), array('field' => 'b', 'order' => 1), array('field' => 'a', 'order' => 1)));
    $s = sort_toggle_field($s, 'c');
    assert($s == array(array('field' => 'c', 'order' => -1), array('field' => 'b', 'order' => 1), array('field' => 'a', 'order' => 1)));
    

