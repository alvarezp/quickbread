<?php

require_once("html_element.php");

class qb_html_page extends qb_html_element {

    protected $head;
    protected $title;
    protected $body;
    protected $stylesheet = array();

    function __construct($title, $attributes = array()) {
        $this->title = $title;
        $this->tag = 'html';

        foreach($attributes as $k => $v) {
            $this->set_attribute($k, $v);
        }

        $this->head = new qb_html_element('head');
        //Can't use $this->add_child because it conflicts with our
        //overriddance below.
        parent::add_child($this->head);

        $this->title = new qb_html_element('title', array(), $this->title, True);
        $this->head->add_child($this->title);

        $this->body = new qb_html_element('body');
        parent::add_child($this->body);
    }

    public function add_child($html_element) {
        $this->body->add_child($html_element);
    }

    public function set_charset($charset) {
        $this->head->add_child(new qb_html_element('meta', array(
            'charset' => $charset
        ), "", False, False));
    }

    public function add_stylesheet($location, $type = "text/css") {
        $this->head->add_child(new qb_html_element('link', array(
            'rel' => "stylesheet",
            'type' => $type,
            'href' => $location
        ), "", False, False));
    }

    public function prepend_child($html_element) {
        $this->body->prepend_child($html_element);
    }

    public function get_html_array() {
        $content = array("<!DOCTYPE html>");
        $content = array_merge($content, parent::get_html_array(-2));
        return $content;
    }

    public function set_title($t) {
        $this->title = $t;
    }

    public function get_html() {
        $r = "";
        foreach($this->get_html_array() as $line) {
            $r .= $line . "\n";
        }
        return $r;
    }
}

## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;

    require_once("html_table.php");

    print("Test simple-example-page...\n");
    $p_expected = array(
      "<!DOCTYPE html>",
      "<html>",
      "<head>",
      "  <title>Example page</title>",
      "</head>",
      "<body>",
      "</body>",
      "</html>"
    );
    $p = new qb_html_page('Example page');
    $result = $p->get_html_array();
    if (!assert($result == $p_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $p_expected)); $n ++) {
            if ($result[$n] != $p_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    }

    print("Test simple-page-with-attributes...\n");
    $p_expected = array(
      "<!DOCTYPE html>",
      "<html class='page-class' id='page-id'>",
      "<head>",
      "  <title>Page name</title>",
      "</head>",
      "<body>",
      "</body>",
      "</html>"
    );
    $p = new qb_html_page('Page name');
    $p->set_attribute('class', 'page-class');
    $p->set_attribute('id', 'page-id');
    $result = $p->get_html_array();
    if (!assert($result == $p_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $p_expected)); $n ++) {
            if ($result[$n] != $p_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    }

    print("Test a-table-that-will-be-used...\n");
    $t_expected = array(
      "<table id='sample-table' class='t1'>",
      "  <thead>",
      "    <tr>",
      "      <th class='object'>Object</th>",
      "    </tr>",
      "  </thead>",
      "  <tbody>",
      "    <tr>",
      "      <td class='object'>Constellation</td>",
      "    </tr>",
      "    <tr>",
      "      <td class='object'>Galaxy</td>",
      "    </tr>",
      "  </tbody>",
      "</table>"
    );
    $t = new qb_html_table('sample-table');
    $t->set_attribute('class', 't1');
    $t->add_data_column('Object', 'object');
    $t->add_row(array('object' => 'Constellation'));
    $t->add_row(array('object' => 'Galaxy'));
    $result = $t->get_html_array();
    if (!assert($result == $t_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $t_expected)); $n ++) {
            if ($result[$n] != $t_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    }

    print("Test a-page-with-the-previous-table...\n");
    $p_expected = array(
      "<!DOCTYPE html>",
      "<html id='page-id' class='page-class'>",
      "<head>",
      "  <title>Page title</title>",
      "</head>",
      "<body>",
      "  <table id='sample-table' class='t1'>",
      "    <thead>",
      "      <tr>",
      "        <th class='object'>Object</th>",
      "      </tr>",
      "    </thead>",
      "    <tbody>",
      "      <tr>",
      "        <td class='object'>Constellation</td>",
      "      </tr>",
      "      <tr>",
      "        <td class='object'>Galaxy</td>",
      "      </tr>",
      "    </tbody>",
      "  </table>",
      "</body>",
      "</html>"
    );
    $p = new qb_html_page('Page title', array('id' => "page-id"));
    $p->set_attribute('class', 'page-class');
    $p->add_child($t);
    $result = $p->get_html_array();
    if (!assert($result == $p_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $p_expected)); $n ++) {
            if ($result[$n] != $p_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    }

    print("Test simple-example-page-with-external-stylesheet...\n");
    $p_expected = array(
      "<!DOCTYPE html>",
      "<html>",
      "<head>",
      "  <title>Example page</title>",
      "  <link rel='stylesheet' type='text/css' href='location.css'>",
      "</head>",
      "<body>",
      "</body>",
      "</html>"
    );
    $p = new qb_html_page('Example page');
    $p->add_stylesheet('location.css');
    $result = $p->get_html_array();
    if (!assert($result == $p_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $p_expected)); $n ++) {
            if ($result[$n] != $p_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    }

    print("Test simple-example-page-with-charset...\n");
    $p_expected = array(
      "<!DOCTYPE html>",
      "<html>",
      "<head>",
      "  <title>Example page</title>",
      "  <meta charset='utf-8'>",
      "</head>",
      "<body>",
      "</body>",
      "</html>"
    );
    $p = new qb_html_page('Example page');
    $p->set_charset('utf-8');
    $result = $p->get_html_array();
    if (!assert($result == $p_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $p_expected)); $n ++) {
            if ($result[$n] != $p_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    }

