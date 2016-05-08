<?php

class HtmlControl extends qb_html_element {

	public function get_supported_types() {}
	public function get_sql_value() {}
	public function set_value_from_sql($v) {}
	public function set_value_from_post($v) {}
	public function get_html_static($basename) {}
	public function get_html_editable($basename) {}
	public function set_option_list($a) {}
	public function set_parameters($a) {
		return 0;
	}

	public function get_sql_update_from_diff($bef, $aft) {
		if (!isset($aft['value'])) {
			return array('change' => 'no', 'value' => NULL);
		}

		if (!isset($bef['value'])) {
			$ret_change = ($aft['value'] != '' ? 'yes' : 'no');
			return array('change' => $ret_change, 'value' => $aft['value']);
		}

		if ($aft['value'] == $bef['value']) {
			return array('change' => 'no', 'value' => NULL);
		}

		if ($aft['value'] == '') {
			return array('change' => 'yes', 'value' => NULL);
		}

		return array('change' => 'yes', 'value' => $aft['value']);
	}

	public function get_required($r) {
		return $this->required;
	}

	public function set_required($r) {
		$this->required = $r;
	}

	public function set_nullable($r) {
		$this->nullable = $r;
	}

	public function get_nullable($r) {
		return $this->nullable;
	}

}

?>
