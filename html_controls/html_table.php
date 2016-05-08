<?php

require_once("html_element.php");
require_once("html_link.php");

class qb_html_table extends qb_html_element {

    protected $have_header;
    protected $columns;
    protected $rows;

    function __construct($name = "") {
        $this->have_header = False;
        $this->columns = array();
        $this->rows = array();
        parent::__construct('table');
        if ($name != "") {
            $this->set_attribute('id', $name);
        }
    }

    public function add_data_column($label, $key, $source = "", $class = "") {
        if ($source == "")
            $source = $key;
        if ($class == "")
            $class = $key;
        if (!isset($this->columns[$key]))
            $this->columns[$key] = array();
        $this->columns[$key] = array_merge($this->columns[$key], array('type' => 'data', 'class' => $class, 'source' => $source, 'label' => $label));
        $this->have_header = True;
    }

    public function add_row($data) {
        $this->rows[] = $data;
    }

    public function set_parameter_name($name) {
        $this->set_parameter_name = $name;
    }

    protected function th($k, $c) {
        return new qb_html_element('th', array('class' => $k), $c['label'], True);
    }
    
    protected function thead() {
        $thead = new qb_html_element('thead');
        $tr = new qb_html_element('tr');
        foreach($this->columns as $k => $c) {
            $tr->add_child($this->th($k, $c));
        }
        $thead->add_child($tr);
        return $thead;
    }

    protected function get_html_data_cell($row, $k, $c) {
        return new qb_html_element('td',
          array('class' => $c['class']),
          $row[$c['source']],
          True
        );
    }

    protected function get_html_row($row) {
        $tr = new qb_html_element('tr');
        foreach($this->columns as $k => $c) {
            $data = $this->get_html_data_cell($row, $k, $c);
            $tr->add_child($data);
        }
        return $tr;
    }

    public function get_html_array($indent = 0) {
        $this->add_child($this->thead());
        $tbody = new qb_html_element('tbody');
        foreach($this->rows as $row) {
            $tbody->add_child($this->get_html_row($row));
        }
        $this->add_child($tbody);
        $return = parent::get_html_array($indent);
        // We must reset children. Otherwise, multiple calls on
        // $this->get_html_array() will just keep duplicating the content.
        $this->children = array();
        return $return;
    }
}

class qb_data_grid extends qb_html_table {

    protected $sort_state;
    protected $sort_parameter_name;
    protected $current_qsa;

    private function sort_toggle_field($sort, $field) {
	    if (!is_array($sort)) {
		    $sort = array();
	    }
	    # If this field already was the most recently clicked, toggle ASC/DESC.
	    if (count($sort) > 0) {
		    if ($sort[0]['field'] == $field) {
			    $sort[0]['order'] *= -1;
			    return $sort;
		    }
	    }
	    # If this field has previously selected, clear it. The next case
	    # will prepend ASC at the start.
	    foreach($sort as $k => $s) {
		    if ($s['field'] == $field) {
			    unset($sort[$k]);
			    break;
		    }
	    }
	    # Not previously chosen? Just add it with ASC at the start.
	    array_unshift($sort, array('field' => $field, 'order' => 1));
	    return $sort;
    }

    function __construct($name, $qs, $sort_parameter_name) {
        $this->sort_state = array();
        $this->sortable = False;
        $this->sort_parameter_name = $sort_parameter_name;
        $this->current_qsa = $qs;
        $this->set_attribute('id', $name);
        parent::__construct();
    }

    public function set_sort_state($sorted_columns) {
        foreach($sorted_columns as $k => $s) {
            if ($s['order'] != 1 and $s['order'] != -1) {
                return False;
            }
            $this->columns[$s['field']]['sort_marker_char'] = $k + 1;
            $this->columns[$s['field']]['sort_marker_class'] = ($s['order'] == 1) ? 'sortmarker_asc' : 'sortmarker_desc';
        }
        $this->sort_state = $sorted_columns;
        return True;
    }

    protected function th($k, $c) {
        $new_qsa = $this->current_qsa;
        $new_qsa[$this->sort_parameter_name] = $this->sort_toggle_field($this->sort_state, $k);
        $sort_url = "?" . http_build_query($new_qsa);

        $th = new qb_html_element('th', array('class' => $c['class']), "", True);
        $a = new qb_html_link($c['label']);
        $a->set_attribute('href', $sort_url, True);
        $th->add_child($a);
        if (isset($c['sort_marker_class']) and isset($c['sort_marker_char'])) {
            $th->add_child(new qb_html_element('',
              array(),
              '&nbsp;',
              True
            ));
            $th->add_child(new qb_html_element('span',
              array('class' => $c['sort_marker_class']),
              $c['sort_marker_char'],
              True
            ));
        }
        return $th;

    }

}

class qb_actionable_data_grid extends qb_data_grid {

    protected $actions = array();
    protected $action_handler = "";
    protected $qsparams = array();
    protected $pkfields = array();

    function __construct($name, $qs, $sort_parameter_name) {
        parent::__construct($name, $qs, $sort_parameter_name);
        $this->columns['__cb__'] = array('type' => 'checkbox', 'class' => '__cb__', 'source' => '__rowid__', 'label' => '');
        $this->have_header = True;
        $this->form = new qb_html_element('form');
        $this->form->set_attribute('action', $this->action_handler);
        $this->form->set_attribute('method', 'get');
    }

    public function set_action_handler($handler) {
        $this->action_handler = $handler;
    }

    public function add_action($label, $name, $value, $handler = "") {
        $new_action = array('label' => $label, 'name' => $name, 'value' => $value);

        if ($handler != "") {
            $new_action['handler'] = $handler;
        }
            
        $this->actions[] = $new_action;
    }

    public function add_qsparam($name, $value) {
        $this->qsparams[$name] = $value;
    }
    
    public function set_pkfields($pkfields) {
        $this->pkfields = $pkfields;
    }
    
    public function add_row($rowid, $data) {
        $this->rows[] = array('rowid' => $rowid, 'data' => $data);
    }

    public function get_html_array($indent = 0) {
        $dt = debug_backtrace();
        if (isset($dt[2]) && $dt[2]['class'] == 'qb_actionable_data_grid') {
            return parent::get_html_array($indent);
        }
        $this->form->set_attribute('action', $this->action_handler, True);
        $this->form->set_attribute('method', 'get');
        foreach ($this->qsparams as $k => $p) {
            $hidden = new qb_html_element('input', array(
              'type' => 'hidden',
              'name' => $k,
              'value' => $p
            ), "", False, False);
            $this->form->add_child($hidden);
        }

        $div = new qb_html_element('div');
        $this->form->add_child($div);

        foreach ($this->actions as $a) {
            $formaction = "";
            if (isset($a['handler'])) {
                $formaction = $a['handler'];
            }
            $button = new qb_html_element('button', array(
              'type' => 'submit',
              'name' => $a['name'],
              'value' => $a['value']
            ), $a['label'], True);
            if ($formaction != "") {
                $button->set_attribute("formaction", $formaction, True);
            }
            $div->add_child($button);
        }
        $this->form->add_child($this);
        return $this->form->get_html_array($indent);
    }

    protected function get_html_checkbox_cell($rowid) {
        $rowidval = http_build_query($rowid);
        $td = new qb_html_element('td',
            array('class' => '__cb__'),
            "",
            True
        );
        $input = new qb_html_element('input',
            array(
                'id' => "__cb__$rowidval",
                'type' => 'checkbox',
                'name' => 'rows[]',
                'value' => $rowidval
            ),
            "",
            True,
            False
        );
        $td->add_child($input);
        return $td;
    }

    protected function get_html_data_cell($row, $k, $c) {
        $rowdata = $row['data'];
        $rowidval = http_build_query($row['rowid']);
        $td = new qb_html_element('td',
          array('class' => $c['class'])
        );
        $label = new qb_html_element('label',
          array('for' => "__cb__$rowidval"),
          $rowdata[$c['source']],
          True
        );
        $td->add_child($label);
        return $td;
    }

    protected function get_html_row($row) {
        $tr = new qb_html_element('tr');
        foreach($this->columns as $k => $c) {
            if ($c['type'] == 'checkbox') {
                $data = $this->get_html_checkbox_cell($row['rowid']);
            } else {
                $data = $this->get_html_data_cell($row, $k, $c);
            }
            $tr->add_child($data);
        }
        return $tr;
    }

    private function get_html_checkbox_th($k, $c) {
        return new qb_html_element('th', array('class' => '__cb__'), "", True);
    }

    protected function th($k, $c) {
        if ($c['type'] == 'checkbox') {
            return $this->get_html_checkbox_th($k, $c);
        }
        return parent::th($k, $c);
    }
}



## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;

    print("Test sample-qb_html_table...\n");
    $t1_expected = array(
      "<table class='t1' id='sample-table'>",
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
    $t1 = new qb_html_table();
    $t1->set_attribute('class', 't1');
    $t1->set_attribute('id', 'sample-table');
    $t1->add_data_column('Object', 'object');
    $t1->add_row(array('object' => 'Constellation'));
    $t1->add_row(array('object' => 'Galaxy'));
    $result = $t1->get_html_array(0);
    if (!assert($result == $t1_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $t1_expected)); $n ++) {
            if ($result[$n] != $t1_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    } while(0);

    print("Test twice-sample-qb_html_table...\n");
    $t1_expected = array(
      "<table class='t1' id='sample-table'>",
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
    $result = $t1->get_html_array(0);
    if (!assert($result == $t1_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $t1_expected)); $n ++) {
            if ($result[$n] != $t1_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    } while(0);

    print("Test sample-qb_data_grid...\n");
    $t2_expected = array(
      "<table id='sample-sorted-table'>",
      "  <thead>",
      "    <tr>",
//      "      <!-- ?catalog=sampledb&schema=public&entity=dummy&sort[0][field]=object&sort[0][order]=-1-->",
      "      <th class='object'><a href='?catalog=sampledb&schema=public&entity=dummy&sort%5B0%5D%5Bfield%5D=object&sort%5B0%5D%5Border%5D=-1'>Object</a>&nbsp;<span class='sortmarker_asc'>1</span></th>",
//      "      <!-- ?catalog=sampledb&schema=public&entity=dummy&sort[0][field]=example&sort[0][order]=1&sort[1][field]=object&sort[1][order]=1-->",
      "      <th class='example'><a href='?catalog=sampledb&schema=public&entity=dummy&sort%5B0%5D%5Bfield%5D=example&sort%5B0%5D%5Border%5D=1&sort%5B1%5D%5Bfield%5D=object&sort%5B1%5D%5Border%5D=1'>Example</a></th>",
      "    </tr>",
      "  </thead>",
      "  <tbody>",
      "    <tr>",
      "      <td class='object'>Constellation</td>",
      "      <td class='example'>Ophiucus</td>",
      "    </tr>",
      "    <tr>",
      "      <td class='object'>Galaxy</td>",
      "      <td class='example'>Andromeda</td>",
      "    </tr>",
      "  </tbody>",
      "</table>"
    );
    $t2 = new qb_data_grid('sample-sorted-table', array('catalog' => 'sampledb', 'schema' => 'public', 'entity' => 'dummy'), 'sort');
    $t2->add_data_column('Object', 'object');
    $t2->add_data_column('Example', 'example');
    $t2->add_row(array('object' => 'Constellation', 'example' => 'Ophiucus'));
    $t2->add_row(array('object' => 'Galaxy', 'example' => 'Andromeda'));
    $t2->set_sort_state(array(array('field' => 'object', 'order' => 1)));
    $result = $t2->get_html_array(0);
    if (!assert($result == $t2_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $t2_expected)); $n ++) {
            if ($result[$n] != $t2_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    } while(0);

    print("Test simpler-qb_actionable_data_grid...\n");
    $t3_expected = array(
      "<form action='x' method='get'>",
      "  <div>",
      "    <button type='submit' name='actActionN' value='actActionV' formaction='action.php'>Action1</button>",
      "  </div>",
      "  <table id='a-sorted-table'>",
      "    <thead>",
      "      <tr>",
      "        <th class='__cb__'></th>",
      "        <th class='object'><a href='?sort%5B0%5D%5Bfield%5D=object&sort%5B0%5D%5Border%5D=-1'>Object</a>&nbsp;<span class='sortmarker_asc'>1</span></th>",
      "        <th class='example'><a href='?sort%5B0%5D%5Bfield%5D=example&sort%5B0%5D%5Border%5D=1&sort%5B1%5D%5Bfield%5D=object&sort%5B1%5D%5Border%5D=1'>Example</a></th>",
      "      </tr>",
      "    </thead>",
      "    <tbody>",
      "      <tr>",
      "        <td class='__cb__'><input id='__cb__object=Constellation' type='checkbox' name='rows[]' value='object=Constellation'></td>",
      "        <td class='object'>",
      "          <label for='__cb__object=Constellation'>Constellation</label>",
      "        </td>",
      "        <td class='example'>",
      "          <label for='__cb__object=Constellation'>Ophiucus</label>",
      "        </td>",
      "      </tr>",
      "      <tr>",
      "        <td class='__cb__'><input id='__cb__object=Galaxy' type='checkbox' name='rows[]' value='object=Galaxy'></td>",
      "        <td class='object'>",
      "          <label for='__cb__object=Galaxy'>Galaxy</label>",
      "        </td>",
      "        <td class='example'>",
      "          <label for='__cb__object=Galaxy'>Andromeda</label>",
      "        </td>",
      "      </tr>",
      "    </tbody>",
      "  </table>",
      "</form>"
    );
    $t3 = new qb_actionable_data_grid('a-sorted-table', array(), 'sort');
    $t3->add_data_column('Object', 'object');
    $t3->add_data_column('Example', 'example');
    $t3->add_row(array('object' => 'Constellation'), array('object' => 'Constellation', 'example' => 'Ophiucus'));
    $t3->add_row(array('object' => 'Galaxy'), array('object' => 'Galaxy', 'example' => 'Andromeda'));
    $t3->set_sort_state(array(array('field' => 'object', 'order' => 1)));
    $t3->set_action_handler('x');
    $t3->add_action('Action1', 'actActionN', 'actActionV', 'action.php');
    $result = $t3->get_html_array(0);
    if (!assert($result == $t3_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $t3_expected)); $n ++) {
            if ($result[$n] != $t3_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    } while(0);

    print("Test sample-qb_actionable_data_grid...\n");
    $t3_expected = array(
      "<form action='#' method='get'>",
      "  <div>",
      "    <button type='submit' name='actView' value='actView' formaction='view-details.php'>View Details</button>",
      "    <button type='submit' name='actInsert' value='actInsert'>Insert</button>",
      "    <button type='submit' name='actDuplicate' value='actDuplicate'>Duplicate</button>",
      "    <button type='submit' name='actModify' value='actModify'>Modify</button>",
      "    <button type='submit' name='actDelete' value='actDelete'>Delete</button>",
      "    <button type='submit' name='actStoredproc' value='lend'>Lend</button>",
      "    <button type='submit' name='actStoredproc' value='return'>Return</button>",
      "  </div>",
      "  <table id='sample-sorted-table'>",
      "    <thead>",
      "      <tr>",
      "        <th class='__cb__'></th>",
      "        <th class='object'><a href='?sort%5B0%5D%5Bfield%5D=object&sort%5B0%5D%5Border%5D=-1'>Object</a>&nbsp;<span class='sortmarker_asc'>1</span></th>",
      "        <th class='example'><a href='?sort%5B0%5D%5Bfield%5D=example&sort%5B0%5D%5Border%5D=1&sort%5B1%5D%5Bfield%5D=object&sort%5B1%5D%5Border%5D=1'>Example</a></th>",
      "      </tr>",
      "    </thead>",
      "    <tbody>",
      "      <tr>",
      "        <td class='__cb__'><input id='__cb__object=Constellation' type='checkbox' name='rows[]' value='object=Constellation'></td>",
      "        <td class='object'>",
      "          <label for='__cb__object=Constellation'>Constellation</label>",
      "        </td>",
      "        <td class='example'>",
      "          <label for='__cb__object=Constellation'>Ophiucus</label>",
      "        </td>",
      "      </tr>",
      "      <tr>",
      "        <td class='__cb__'><input id='__cb__object=Galaxy' type='checkbox' name='rows[]' value='object=Galaxy'></td>",
      "        <td class='object'>",
      "          <label for='__cb__object=Galaxy'>Galaxy</label>",
      "        </td>",
      "        <td class='example'>",
      "          <label for='__cb__object=Galaxy'>Andromeda</label>",
      "        </td>",
      "      </tr>",
      "    </tbody>",
      "  </table>",
      "</form>"
    );
    $t3 = new qb_actionable_data_grid('sample-sorted-table', array(), 'sort');
    $t3->add_data_column('Object', 'object');
    $t3->add_data_column('Example', 'example');
    $t3->add_row(array('object' => 'Constellation'), array('object' => 'Constellation', 'example' => 'Ophiucus'));
    $t3->add_row(array('object' => 'Galaxy'), array('object' => 'Galaxy', 'example' => 'Andromeda'));
    $t3->set_sort_state(array(array('field' => 'object', 'order' => 1)));
    $t3->set_action_handler('#');
    $t3->add_action('View Details', 'actView', 'actView', 'view-details.php');
    $t3->add_action('Insert', 'actInsert', 'actInsert');
    $t3->add_action('Duplicate', 'actDuplicate', 'actDuplicate');
    $t3->add_action('Modify', 'actModify', 'actModify');
    $t3->add_action('Delete', 'actDelete', 'actDelete');
    $t3->add_action('Lend', 'actStoredproc', 'lend');
    $t3->add_action('Return', 'actStoredproc', 'return');
    $result = $t3->get_html_array(0);
    if (!assert($result == $t3_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $t3_expected)); $n ++) {
            if ($result[$n] != $t3_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    } while(0);

