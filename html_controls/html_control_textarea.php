<?php

require_once ("html_control.php");

class HtmlControlTextarea extends HtmlControl {

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

		$r .= "<span class='textbox' id='${basename}'>\n";

		$required_html = $this->required ? "required='required' placeholder='(Required)'" : "";

		if (isset($this->value)) {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value='$this->value'>\n";
			$r .= "<textarea id='${basename}[aft][value]' type='text' name='${basename}[aft][value]' class='textbox' $required_html>$this->value</textarea>\n";
		} else {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value=''>\n";
			$r .= "<textarea id='${basename}[aft][value]' type='text' name='${basename}[aft][value]' class='textbox' $required_html></textarea>\n";
		}
		$r .= "</span>";

		return $r;
	}

	public function get_html_static($basename) {
		$r = "";

		$r .= "<span class='textarea' id='${basename}'>\n";

		if (isset($this->value)) {
			$r .= "<span class='known'>" . htmlspecialchars($this->value) . "</span>\n";
		} else {
			$r .= "<span class='unknown'>Not&nbsp;set</span>\n";
		}
		$r .= "</span>";

		return $r;
	}

}

?>
