<?php

require_once ("html_control.php");

class HtmlControlCalendar extends HtmlControl {

	private $value;

	public function get_supported_types() {
		return array(
			array(type => 'date', priority => '0')
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
			$r .= "<input type='hidden' name='${basename}[bef][value]' value='$this->value'></input>\n";
			$r .= "<input id='${basename}[aft][value]' type='date' name='${basename}[aft][value]' class='textbox' value='$this->value' $required_html></input>\n";
		} else {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value=''></input>\n";
			$r .= "<input id='${basename}[aft][value]' type='date' name='${basename}[aft][value]' class='textbox' value='' $required_html></input>\n";
		}
		$r .= "</span>";

		return $r;
	}

	public function get_html_static($basename) {
		$r = "";

		$r .= "<span class='calendar' id='${basename}'>\n";

		if (isset($this->value)) {
			$r .= "<span class='calendar_known'>" . htmlspecialchars($this->value) . "</span>\n";
		} else {
			$r .= "<span class='calendar_unknown'>Not&nbsp;set</span>\n";
		}
		$r .= "</span>";

		return $r;
	}

	public function get_required($r) {
		return $this->required;
	}

	public function set_required($r) {
		$this->required = $r;
	}

}

?>
