<?php

require_once ("html_control.php");

class HtmlControlPassword extends HtmlControl {

	private $value;

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

	public function get_html_editable($basename) {
		$r = "";

		$r .= "<span class='passwordbox' id='${basename}'>\n";

		$required_html = $this->required ? "required='required' placeholder='(Required)'" : "";

		if (isset($this->value)) {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value='1234'></input>\n";
			$r .= "<input id='${basename}[aft][value]' type='password' name='${basename}[aft][value]' class='passwordbox' value='1234' $required_html></input>\n";
		} else {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value=''></input>\n";
			$r .= "<input id='${basename}[aft][value]' type='password' name='${basename}[aft][value]' class='passwordbox' value='' $required_html></input>\n";
		}
		$r .= "</span>";

		return $r;
	}

	public function get_html_static($basename) {
		$r = "";

		$r .= "<span class='passwordbox' id='${basename}'>\n";

		if (isset($this->value)) {
			$r .= "<span class='passwordbox_known'>****</span>\n";
		} else {
			$r .= "<span class='passwordbox_unknown'>Not set</span>\n";
		}
		$r .= "</span>";

		return $r;
	}

}

?>
