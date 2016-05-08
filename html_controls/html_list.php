<?php

require_once("html_element.php");

class qb_html_litem extends qb_html_element {
    protected $content;

    function __construct($content, $attributes = array()) {
        parent::__construct('li', $attributes);
        $this->singleline = True;
        $this->add_child($content);
    }

    function add_child($content) {
        if (gettype($content) == "string") {
            parent::add_child(new qb_html_element('', array(), $content));
        } else {
            parent::add_child($content);
        }
    }
}

class qb_html_ulist extends qb_html_element {

    protected $items = array();

    function __construct($attributes = array()) {
        parent::__construct('ul', $attributes);
    }

    public function add_item($content, $attributes = array()) {
        $this->items[] = array('content' => $content, 'attributes' => $attributes);
    }

    public function get_html_array($indent = 0) {
        foreach($this->items as $item) {
            $this->add_child(new qb_html_litem($item['content'], $item['attributes']));
        }
        $return = parent::get_html_array($indent);
        $this->children = array();
        return $return;
    }
}

class qb_html_olist extends qb_html_ulist {
    function __construct($attributes = array()) {
        parent::__construct($attributes);
        $this->tag = 'ol';
    }
}

## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;


print("Test item-empty...\n");
$expected = array(
  "<li>x</li>"
);
$t = new qb_html_litem("x");
$result = $t->get_html_array(0);
if (!assert($result == $expected))
    print_r($result);


print("Test item-text...\n");
$expected = array(
  "<li>Content</li>"
);
$t = new qb_html_litem("Content");
$result = $t->get_html_array(0);
if (!assert($result == $expected))
    print_r($result);


print("Test item-text-class...\n");
$expected = array(
  "<li class='example'>Content</li>"
);
$t = new qb_html_litem("Content", array('class' => "example"));
$result = $t->get_html_array(0);
if (!assert($result == $expected))
    print_r($result);


print("Test ulist-empty...\n");
$expected = array(
  "<ul>",
  "</ul>"
);
$t = new qb_html_ulist();
$result = $t->get_html_array(0);
if (!assert($result == $expected))
    print_r($result);


print("Test ulist-one-text-item...\n");
$expected = array(
  "<ul>",
  "  <li>THIS IS THE TEXT</li>",
  "</ul>"
);
$t = new qb_html_ulist();
$t->add_item("THIS IS THE TEXT");
$result = $t->get_html_array(0);
if (!assert($result == $expected)) {
    print_r($result);
    for ($n = 0; $n < count(min($result, $expected)); $n ++) {
        if ($result[$n] != $expected[$n]) {
            print("Check out line $n\n");
        }
    }
} while(0);


print("Test ulist-two-text-item...\n");
$expected = array(
  "<ul>",
  "  <li>THIS IS THE FIRST TEXT</li>",
  "  <li>THIS IS THE SECOND TEXT</li>",
  "</ul>"
);
$t = new qb_html_ulist();
$t->add_item("THIS IS THE FIRST TEXT");
$t->add_item("THIS IS THE SECOND TEXT");
$result = $t->get_html_array(0);
if (!assert($result == $expected)) {
    print_r($result);
    for ($n = 0; $n < count(min($result, $expected)); $n ++) {
        if ($result[$n] != $expected[$n]) {
            print("Check out line $n\n");
        }
    }
} while(0);


print("Test ulist-one-p-item...\n");
$expected = array(
  "<ul>",
  "  <li><p>THIS IS THE TEXT</p></li>",
  "</ul>"
);
$t = new qb_html_ulist();
$t->add_item(new qb_html_element('p', array(), "THIS IS THE TEXT", True));
$result = $t->get_html_array(0);
if (!assert($result == $expected)) {
    print_r($result);
    for ($n = 0; $n < count(min($result, $expected)); $n ++) {
        if ($result[$n] != $expected[$n]) {
            print("Check out line $n\n");
        }
    }
} while(0);


print("Test ulist-two-p-item...\n");
$expected = array(
  "<ul>",
  "  <li><p>THIS IS THE FIRST TEXT</p></li>",
  "  <li><p>THIS IS THE SECOND TEXT</p></li>",
  "</ul>"
);
$t = new qb_html_ulist();
$t->add_item(new qb_html_element('p', array(), "THIS IS THE FIRST TEXT", True));
$t->add_item(new qb_html_element('p', array(), "THIS IS THE SECOND TEXT", True));
$result = $t->get_html_array(0);
if (!assert($result == $expected)) {
    print_r($result);
    for ($n = 0; $n < count(min($result, $expected)); $n ++) {
        if ($result[$n] != $expected[$n]) {
            print("Check out line $n\n");
        }
    }
} while(0);



print("Test olist-two-p-item...\n");
$expected = array(
  "<ol>",
  "  <li><p>THIS IS THE FIRST TEXT</p></li>",
  "  <li><p>THIS IS THE SECOND TEXT</p></li>",
  "</ol>"
);
$t = new qb_html_olist();
$t->add_item(new qb_html_element('p', array(), "THIS IS THE FIRST TEXT", True));
$t->add_item(new qb_html_element('p', array(), "THIS IS THE SECOND TEXT", True));
$result = $t->get_html_array(0);
if (!assert($result == $expected)) {
    print_r($result);
    for ($n = 0; $n < count(min($result, $expected)); $n ++) {
        if ($result[$n] != $expected[$n]) {
            print("Check out line $n\n");
        }
    }
} while(0);



print("Test nested-one-text-item-ulist...\n");
$expected = array(
  "<ol>",
  "  <li><ol>",
  "    <li>TEXT</li>",
  "  </ol></li>",
  "</ol>"
);
$l1 = new qb_html_olist();
$l2 = new qb_html_olist();
$l2->add_item("TEXT");
$l1->add_item($l2);
$result = $l1->get_html_array(0);
if (!assert($result == $expected)) {
    print_r($result);
    for ($n = 0; $n < count(min($result, $expected)); $n ++) {
        if ($result[$n] != $expected[$n]) {
            print("Check out line $n\n");
        }
    }
} while(0);




