<?php

require_once("html_element.php");

class qb_html_link extends qb_html_element {

    protected $content;
    protected $href;
    protected $title;

    public function __construct($content, $href = "#", $title = "", $attributes = array()) {
        $this->content = $content;
        $this->tag = 'a';
        $this->singleline = True;
        foreach($attributes as $k => $v) {
            $this->set_attribute($k, $v);
        }
        $this->set_attribute('href', $href);
        if ($title != "") {
            $this->set_attribute('title', $title);
        }
        if (gettype($content) == "string")
            $this->add_child(new qb_html_element('', array(), $content));
        else
            $this->add_child($content);
    }

}



## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;

    print("Test simplest-link\n");
    $e_expected = array(
      "<a href='#'>Text</a>"
    );
    $e = new qb_html_link("Text");
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

    print("Test link-to-image\n");
    $e_expected = array(
      "<a href='#'><img src='source.svg'></a>"
    );
    $img = new qb_html_element('img', array('src' => "source.svg"), "", False, False);
    $e = new qb_html_link($img);
    $result = $e->get_html_array();
    assert($result == $e_expected) or print_r($result);

