<?php

require_once ("html_control.php");

class HtmlControlDropdown extends HtmlControl {

	private $value;
	private $option_list;

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
		$this->option_list = $a;
	}

	public function get_html_editable($basename) {
		$r = "";

		$r .= "<span class='dropdown' id='${basename}'>\n";

		$required_html = $this->required ? "required='required' placeholder='(Required)'" : "";

		if (isset($this->value)) {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value='$this->value' $required_html></input>\n";
			$r .= "<select id='${basename}[aft][value]' type='text' name='${basename}[aft][value]' class='dropdown'>";
			if ($this->nullable) {
				$r .= " <option value='' selected>(Not set)</option>";
			}
			if ($this->option_list) {
				foreach ($this->option_list as $v) {
					foreach ($v as $f) {
						if ($this->value == $f) {
							$r .= " <option value='$f' selected>$f</option>";
						} else {
							$r .= " <option value='$f'>$f</option>";
						}
					}
				}
			}
			$r .= "</select>\n";
		} else {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value='' $required_html></input>\n";
			$r .= "<select id='${basename}[aft][value]' type='text' name='${basename}[aft][value]' class='dropdown'>";
			if ($this->nullable) {
				$r .= " <option value='' selected>(Not set)</option>";
			}
			if ($this->option_list) {
				foreach ($this->option_list as $v) {
					foreach ($v as $f) {
						$r .= " <option value='$f'>$f</option>";
					}
				}
			}
			$r .= "</select>\n";
		}
		$r .= "</span>";

		return $r;
	}

	public function get_html_static($basename) {
		$r = "";

		$r .= "<span class='dropdown' id='${basename}'>\n";

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
