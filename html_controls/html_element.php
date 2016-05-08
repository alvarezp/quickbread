<?php

class qb_html_element {

    protected $tag;
    protected $closed = True;
    protected $children = array();
    protected $text;
    private $attributes = array();
    protected $singleline;
    
    public function add_child($html_element) {
        $this->children[] = $html_element;
    }

    public function prepend_child($html_element) {
        array_unshift($this->children, $html_element);
    }

    public function set_text($string) {
        $this->text = $string;
    }


    function __construct($tag, $attributes = array(), $text = "", $singleline = False, $closed = True) {
        if (!preg_match('/^([a-zA-Z0-9]+)?$/', $tag))
            die("Tag outside of charset specification: $tag\n");

        foreach($attributes as $aname => $aval) {
            if (preg_match('/[\x00-\x1F\x7F \\/>"\'=]/', $aname)) {
                die("Attribute name outside of charset speficiation\n");
            }
        }

        $this->closed = $closed;

        $this->tag = $tag;
        $this->text = $text;
        $this->singleline = $singleline;
        foreach($attributes as $k => $d) {
            // The attribute value must be passed as a direct string or an
            // array with indexes 'value' and 'safe'.
            $value = "";
            $safe = False;
            if (is_string($d)) {
                $value = $d;
            } else {
                if (isset($d[0])) {
                    $value = $d[0];
                    $safe = $d[1];
                } elseif (isset($d['value'])) {
                    $value = $d['value'];
                    $safe = $d['safe'];
                }
            }
//            print("Setting attribute '$k' to '$value' with safety = '$safe'\n");
            $this->set_attribute($k, $value, $safe);
        }
    }

    public function set_attribute($key, $value, $safe = False) {
        $this->attributes[$key] = array('value' => $value, 'safe' => $safe);
    }

    private function escape_attr_val($s) {
        return htmlentities($s); 
    }

    private function stringify_attr($key, $data) {
        if (!isset($data['value']) or $data['value'] == "")
            return $key;
        if (!isset($data['safe']))
            $data['safe'] = False;
        if ($data['safe'] == True)
            return "$key='${data['value']}'";
        return "$key='" . $this->escape_attr_val($data['value']) . "'";
    }

    private function get_attributes_as_strings_array() {
        $r = array();
        foreach($this->attributes as $k => $d) {
            $r[] = $this->stringify_attr($k, $d);
        }
        return $r;
    }

    private function get_attributes_as_string() {
        return implode(" ", $this->get_attributes_as_strings_array());
    }

    public function get_html_array($indent = 0) {
        $r = array();
        $prefix = str_repeat(" ", max(0, $indent));

        $tagname = $this->tag;
        
        if ($this->tag == '') {
            return array($prefix . $this->text);
        }
        
        $nested_indent = $indent;
        if (!$this->singleline)
            $nested_indent = $indent + 2;
        $prefix_nested = str_repeat(" ", max(0, $nested_indent));
        
        $opentag = "<" .
          join(" ",
            array_filter(array($tagname, $this->get_attributes_as_string()))
          ) .
          ">";

        if ($this->closed == False) {
            return array($prefix . $opentag);
        }

        $r[] = $prefix . $opentag;

        if ($this->text != "") {
            if ($this->singleline) {
                $s = array_pop($r);
                $r[] = $s . $this->text;
            } else {
                $r[] = "${prefix_nested}$this->text";
            }
        }
        foreach($this->children as $c) {
            if ($this->singleline) {
                $chcode = $c->get_html_array($indent);
                $s = array_pop($r);
                $r[] = $s . substr($chcode[0], $indent);
                array_shift($chcode);
            } else {
                $chcode = $c->get_html_array($nested_indent);
            }
            $r = array_merge($r, $chcode);
        }

        $closetag = "</$tagname>";
        if ($this->singleline) {
            $r[] = array_pop($r) . $closetag;
        } else {
            $r[] = $prefix . $closetag;
        }

        
        return $r;
#-------        
        if ($this->singleline) {
            $string = "$prefix<" .
              join(" ",
                array_filter(array($tagname, $this->get_attributes_as_string()))
              ) .
              ">";
            if ($this->closed == True) {
                if ($this->text != "")
                    $string .= "$this->text";
                foreach($this->children as $c) {
                    $string .= $c->get_html_array(0);
                }
                $string .= "</$tagname>";
            }

            return array($string);
        }

        $r[] = "$prefix<" .
          join(" ",
            array_filter(array($tagname, $this->get_attributes_as_string()))
          ) .
          ">";
        if ($this->closed == True) {
            if ($this->text != "")
                $r[] = "${prefix_nested}$this->text";
            foreach($this->children as $c) {
                $r = array_merge($r, $c->get_html_array($indent + 2));
            }
            $r[] = "$prefix</$tagname>";
        }

        return $r;
    }

    public function get_html($indent = 0) {
        $prefix = "";
        if ($indent > 0)
            $prefix = str_repeat(" ", $indent);
        $r = "";
        foreach($this->get_html_array() as $line) {
            $r .= "${prefix}${line}\n";
        }
        return $r;
    }
}


## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;

    print("Test simple-element\n");
    $e_expected = array(
      "<div>",
      "</div>"
    );
    $e = new qb_html_element('div');
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test element-with-attribute\n");
    $e_expected = array(
      "<div id='abc'>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => 'abc'));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test element-with-safe-attribute\n");
    $e_expected = array(
      "<div id='ab&c'>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => array('ab&c', True)));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test element-with-unsafe-attribute-syntax1\n");
    $e_expected = array(
      "<div id='ab&amp;c'>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => 'ab&c'));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test element-with-unsafe-attribute-syntax2\n");
    $e_expected = array(
      "<div id='ab&amp;c'>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => array('ab&c', False)));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test element-with-unvalued-attribute\n");
    $e_expected = array(
      "<div enabled>",
      "</div>"
    );
    $e = new qb_html_element('div', array('enabled' => ''));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test element-with-child\n");
    $e_expected = array(
      "<div id='abcde'>",
      "  <p class='classy'>",
      "  </p>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => 'abcde'));
    $e->add_child(new qb_html_element('p', array('class' => 'classy')));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test element-with-child-with-content\n");
    $e_expected = array(
      "<div id='abcde'>",
      "  <p class='classy'>",
      "    Hola",
      "  </p>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => 'abcde'));
    $e->add_child(new qb_html_element('p', array('class' => 'classy'), "Hola"));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test nested-child\n");
    $e_expected = array(
      "<div id='abcde'>",
      "  <p class='classy'>",
      "    <img src='123' alt='image'>",
      "  </p>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => 'abcde'));
    $p = new qb_html_element('p', array('class' => 'classy'));
    $i = new qb_html_element('img', array('src' => '123', 'alt' => 'image'), "", False, False);
    $p->add_child($i);
    $e->add_child($p);
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test untagged-element\n");
    $e_expected = array(
      "<div id='abcde'>",
      "  <p class='classy'>",
      "    Some text",
      "  </p>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => 'abcde'));
    $p = new qb_html_element('p', array('class' => 'classy'));
    $i = new qb_html_element('', array(), "Some text");
    $p->add_child($i);
    $e->add_child($p);
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test singleline-element\n");
    $e_expected = array(
      "<a href='abcde'>LINK</a>",
    );
    $e = new qb_html_element('a', array('href' => 'abcde'), "LINK", True);
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test singleline-element-in-div\n");
    $e_expected = array(
      "<div>",
      "  <a href='abcde'>LINK</a>",
      "</div>"
    );
    $e = new qb_html_element('div');
    $e->add_child(new qb_html_element('a', array('href' => 'abcde'), "LINK", True));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test contentful-div-in-singleline-elment\n");
    $e_expected = array(
      "<a href='abcde'><div>",
      "  Some content",
      "</div></a>"
    );
    $e = new qb_html_element('a', array('href' => 'abcde'), "", True);
    $e->add_child(new qb_html_element('div', array(), "Some content"));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test multichildren-in-nested-in-singleline-elment\n");
    $e_expected = array(
      "<a href='abcde'><div>",
      "  Some content",
      "  Some more",
      "</div></a>"
    );
    $e = new qb_html_element('a', array('href' => 'abcde'), "", True);
    $div = new qb_html_element('div', array());
    $e->add_child($div);
    $div->add_child(new qb_html_element('', array(), "Some content"));
    $div->add_child(new qb_html_element('', array(), "Some more"));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test multichildren-in-nested-in-singleline-elment\n");
    $e_expected = array(
      "<a href='abcde'><span>Some contentSome more</span></a>"
    );
    $e = new qb_html_element('a', array('href' => 'abcde'), "", True);
    $div = new qb_html_element('span', array(), "", True);
    $e->add_child($div);
    $div->add_child(new qb_html_element('', array(), "Some content"));
    $div->add_child(new qb_html_element('', array(), "Some more"));
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test ulist-one-text-item...\n");
    $expected = array(
      "<ul>",
      "  <li>THIS IS THE TEXT</li>",
      "</ul>"
    );
    $t = new qb_html_element('ul');
    $t->add_child(new qb_html_element('li', array(), "THIS IS THE TEXT", True));
    $result = $t->get_html_array(0);
    if (!assert($result == $expected)) {
        print_r($result);
    }

    print("Test ulist-one-tagless-child...\n");
    $expected = array(
      "<ul>",
      "  <li>THIS IS THE TEXT</li>",
      "</ul>"
    );
    $t = new qb_html_element('ul');
    $l = new qb_html_element('li', array(), "", True);
    $l->add_child(new qb_html_element('', array(), "THIS IS THE TEXT"));
    $t->add_child($l);
    $result = $t->get_html_array(0);
    if (!assert($result == $expected)) {
        print_r($result);
    }




    print("Test htmlstringresult-simple-element\n");
    $e_expected =
      "<div>\n" .
      "</div>\n";
    $e = new qb_html_element('div');
    $result = $e->get_html();
    assert($result == $e_expected) or print_r($result);

    print("Test htmlstringresult-element-with-child\n");
    $e_expected = 
      "<div id='abcde'>\n" .
      "  <p class='classy'>\n" .
      "  </p>\n" .
      "</div>\n";
    $e = new qb_html_element('div', array('id' => 'abcde'));
    $e->add_child(new qb_html_element('p', array('class' => 'classy')));
    $result = $e->get_html();
    assert($result == $e_expected) or print_r($result);

    print("Test htmlstringresult-element-with-child-with-content\n");
    $e_expected =
      "<div id='abcde'>\n" .
      "  <p class='classy'>\n" .
      "    Hola\n" .
      "  </p>\n" .
      "</div>\n";
    $e = new qb_html_element('div', array('id' => 'abcde'));
    $e->add_child(new qb_html_element('p', array('class' => 'classy'), "Hola"));
    $result = $e->get_html();
    assert($result == $e_expected) or print_r($result);

    print("Test htmlstringresult-nested-child\n");
    $e_expected =
      "<div id='abcde'>\n" .
      "  <p class='classy'>\n" .
      "    <img src='123' alt='image'>\n" .
      "  </p>\n" .
      "</div>\n";
    $e = new qb_html_element('div', array('id' => 'abcde'));
    $p = new qb_html_element('p', array('class' => 'classy'));
    $i = new qb_html_element('img', array('src' => '123', 'alt' => 'image'), "", False, False);
    $p->add_child($i);
    $e->add_child($p);
    $result = $e->get_html();
    assert($result == $e_expected) or print_r($result);

    print("Test htmlstringresult-untagged-element\n");
    $e_expected = array(
      "<div id='abcde'>",
      "  <p class='classy'>",
      "    Some text",
      "  </p>",
      "</div>"
    );
    $e = new qb_html_element('div', array('id' => 'abcde'));
    $p = new qb_html_element('p', array('class' => 'classy'));
    $i = new qb_html_element('', array(), "Some text");
    $p->add_child($i);
    $e->add_child($p);
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test htmlstringresult-singleline-element\n");
    $e_expected = "<a href='abcde'>LINK</a>\n";
    $e = new qb_html_element('a', array('href' => 'abcde'), "LINK", True);
    $result = $e->get_html();
    assert($result == $e_expected) or print_r($result);

    print("Test htmlstringresult-singleline-element-in-div\n");
    $e_expected = 
      "<div>\n" .
      "  <a href='abcde'>LINK</a>\n" .
      "</div>\n";
    $e = new qb_html_element('div');
    $e->add_child(new qb_html_element('a', array('href' => 'abcde'), "LINK", True));
    $result = $e->get_html();
    assert($result == $e_expected) or print_r($result);

