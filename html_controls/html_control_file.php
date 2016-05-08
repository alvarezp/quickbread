<?php

require_once ("html_control.php");

/* Shamelessly taken from the PHP documentation */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

class HtmlControlFile extends HtmlControl {

	private $value;
	private $parameters;

	public function get_supported_types() {
		return array(
			array(type => 'xml', priority => '0')
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
		$maxsize = min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size')) - 1024);

		$r = "";

		$r .= "<span class='html_control_file' id='${basename}'>\n";

		$required_html = $this->required ? "required='required' placeholder='(Required)'" : "";

		if (isset($this->value)) {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value='$this->value'></input>\n";
			$r .= "<input type='hidden' name='MAX_FILE_SIZE' value='${maxsize}' />\n";
			$r .= "Replace with file: <input id='${basename}[aft][value]' type='file' name='${basename}[aft][value]' class='file' $required_html />\n";
		} else {
			$r .= "<input type='hidden' name='${basename}[bef][value]' value=''></input>\n";
			$r .= "<input type='hidden' name='MAX_FILE_SIZE' value='${maxsize}' />\n";
			$r .= "<input id='${basename}[aft][value]' type='file' name='${basename}[aft][value]' class='file' $required_html />\n";
		}
		$r .= "</span>";

		return $r;
	}

	public function set_parameters($a) {
		$this->parameters = $a;
	}

	public function get_html_static($basename) {
		$r = "";

		$r .= "<span class='html_control_file' id='${basename}'>\n";

		if (isset($this->value)) {
#			$r .= "<span class='known'>(Document&nbsp;exists)</span>\n";
			$r .= "<span class='known'><a href='download.php?" . $this->parameters . "'>Download...</a></span>\n";
		} else {
			$r .= "<span class='unknown'>(Not&nbsp;set)</span>\n";
		}
		$r .= "</span>";

		return $r;
	}

}

?>
