<?php

require_once("html_element.php");

class qb_html_text extends qb_html_element {

    function __construct($content) {
        parent::__construct('', array(), $content);
    }

}




## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;


print("Test empty...\n");
$expected = array(
  ""
);
$t1 = new qb_html_text("");
$result = $t1->get_html_array(0);
if (!assert($result == $expected))
    print_r($result);


print("Test some-text...\n");
$expected = array(
  "TEXT VALUE"
);
$t1 = new qb_html_text("TEXT VALUE");
$result = $t1->get_html_array(0);
if (!assert($result == $expected))
    print_r($result);



