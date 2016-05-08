<?php

require_once ("html_control_new.php");

class HtmlControlTextbox extends HtmlControl {

	public function get_supported_types() {
		return array(
			array(type => 'character varying', priority => '0')
		);
	}

	public function get_sql_value() {
		if (isset($his->value)) {
			return $this->value;
		} else {
			return NULL;
		}
	}

	public function set_value_from_sql($v) {
		$this->value = $v;
	}

	public function set_value_from_post($v) {
		$this->value = $v;
	}

	public function set_option_list($a) {
	}

    public function __construct($basename) {
        parent::__construct($basename);
        $this->set_attribute('class', 'textbox');
    }

    public function get_html_array_editable($indent = 0) {
        $value = new qb_html_element('span');
        if (isset($this->value)) {
            $value->set_attribute('class', "known");
            $value->set_text($this->value);
        } else {
            $value->set_attribute('class', "unknown");
            $value->set_text("Not&nbsp;set");
        }
        $this->add_child($value);
        $r = parent::get_html_array($indent);
        $this->children = array();
        return $r;
    }

    public function get_html_array_static($indent = 0) {
        $input_control = new qb_html_element('input', array(
            'id' => "$this->basename[aft][value]",
            'type' => "text",
            'name' => "$this->basename[aft][value]",
            'class' => "textbox"
        ), "", False, False);
        if ($this->required) {
            $input_control->set_attribute('required', '');
            $input_control->set_attribute('placeholder', '(Required)');
        }
        if (isset($this->value)) {
            $input_control->set_attribute('value', $this->value);
            $input_before = new qb_html_element('input', array(
                'type' => "hidden",
                'name' => "$this->basename[bef][value]",
                'value' => $this->value,
            ), "", False, False);
            $this->add_child($input_before);
        }
        $this->add_child($input_control);
        $r = parent::get_html_array($indent);
        $this->children = array();
        return $r;
    }
    
    public function get_html_array($indent = 0) {
        if (!$this->editable) {
            return $this->get_html_array_editable($indent);
        } else {
            return $this->get_html_array_static($indent);
        }
    }
	
}


if ((!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__))
    return;

print("Test textbox-editable-with-value...\n");
$c = new HtmlControlTextbox("BASE");
$c->set_required(True);
$c->set_value_from_sql('OCTAVIO ALVAREZ');
$c->set_editable(True);
$result = $c->get_html_array();
$expected = array(
    "<span id='BASE' class='textbox'>",
    "  <input type='hidden' name='BASE[bef][value]' value='OCTAVIO ALVAREZ'>",
    "  <input id='BASE[aft][value]' type='text' name='BASE[aft][value]' class='textbox' required placeholder='(Required)' value='OCTAVIO ALVAREZ'>",
    "</span>"
);
if (!assert($result == $expected)) {
    print_r($result);
    for($i = 0; $i < min(count($expected), count($result)); $i++)
        if ($expected[$i] != $result[$i])
            print("Check out line $i:\n");
}

print("Test textbox-static-with-value...\n");
$c->set_editable(False);
$result = $c->get_html_array();
$expected = array(
    "<span id='BASE' class='textbox'>",
    "  <span class='known'>",
    "    OCTAVIO ALVAREZ",
    "  </span>",
    "</span>"
);
if (!assert($result == $expected)) {
    print_r($result);
    for($i = 0; $i < min(count($expected), count($result)); $i++)
        if ($expected[$i] != $result[$i])
            print("Check out line $i:\n");
}

print("Test textbox-editable-without-value...\n");
$c = new HtmlControlTextbox("BASE");
$c->set_required(True);
$c->set_editable(True);
$result = $c->get_html_array();
$expected = array(
    "<span id='BASE' class='textbox'>",
    "  <input id='BASE[aft][value]' type='text' name='BASE[aft][value]' class='textbox' required placeholder='(Required)'>",
    "</span>"
);
if (!assert($result == $expected)) {
    print_r($result);
    for($i = 0; $i < min(count($expected), count($result)); $i++)
        if ($expected[$i] != $result[$i])
            print("Check out line $i:\n");
}

print("Test textbox-static-without-value...\n");
$c->set_editable(False);
$result = $c->get_html_array();
$expected = array(
    "<span id='BASE' class='textbox'>",
    "  <span class='unknown'>",
    "    Not&nbsp;set",
    "  </span>",
    "</span>"
);
if (!assert($result == $expected)) {
    print_r($result);
    for($i = 0; $i < min(count($expected), count($result)); $i++)
        if ($expected[$i] != $result[$i])
            print("Check out line $i:\n");
}

