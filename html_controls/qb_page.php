<?php

require_once("html_page.php");
require_once("html_link.php");

class qb_widget_page extends qb_html_page {

    public $appname;
    public $menu_links = array();
    
    public function __construct($appname, $title, $attributes = array(), $charset = 'utf-8') {
        $this->appname = $appname;
        parent::__construct($title, $attributes);
        $this->add_stylesheet('default_style.css');
        $this->set_charset($charset);
    }

    public function add_menu_link($text, $href = "#", $title = "", $attributes = array()) {
        $this->menu_links[] = array(
          'text' => $text,
          'href' => $href,
          'title' => $title,
          'attributes' => $attributes
        );
    }

    public function get_html_array() {
        $header = new qb_html_element('header');
        $titlebar = new qb_html_element('div', array('id' => 'titlebar'));
        $header->add_child($titlebar);
        $titlebar->add_child(
          new qb_html_element('div', array('id' => "title"), $this->appname, True)
        );
        $titleicons = new qb_html_element('div', array('id' => "title-icons"));
        // Add the Reports icon
        $titleicons_reports_div = new qb_html_element('div', array('id' => "reports"));
        $titleicons_reports_div_link = new qb_html_link(
            new qb_html_element('img', array('src' => "/images/Text-x-generic.svg"), "", False, False),
            "#",
            "Reports (not working yet)"
        );
        $titleicons_reports_div->add_child($titleicons_reports_div_link);
        $titleicons->add_child($titleicons_reports_div);
        // Add the Settings icon
        $titleicons_settings_div = new qb_html_element('div', array('id' => "settings"));
        $titleicons_settings_div_link = new qb_html_link(
            new qb_html_element('img', array('src' => "/images/Emblem-system.svg"), "", False, False),
            "#",
            "Settings (not working yet)"
        );
        $titleicons_settings_div->add_child($titleicons_settings_div_link);
        $titleicons->add_child($titleicons_settings_div);
        // Add the Notifications icon
        $titleicons_notifications_div = new qb_html_element('div', array('id' => "notifications"));
        $titleicons_notifications_div_link = new qb_html_link(
            new qb_html_element('img', array('src' => "/images/Mail-closed.svg"), "", False, False),
            "#",
            "Notifications (not working yet)"
        );
        $titleicons_notifications_div->add_child($titleicons_notifications_div_link);
        $titleicons->add_child($titleicons_notifications_div);
        
        $titlebar->add_child($titleicons);

        if (count($this->menu_links) > 0) {
            $nav = new qb_html_element('nav', array('id' => "menubar"));
            foreach ($this->menu_links as $l) {
                $nav->add_child(
                    new qb_html_link(
                        $l['text'], 
                        $l['href'], 
                        $l['title'], 
                        array_merge(array('class' => 'entity'), $l['attributes'])
                    )
                );
            }
            $header->add_child($nav);
        }

        $this->prepend_child($header);

        $return = parent::get_html_array();
        $this->children = array();
        return $return;
    }    
}



## UNIT TESTS ###############################################################
if (!isset($_SERVER['argc']) || !realpath($argv[0]) == __FILE__)
    return;

    print("Test simple-new-qb-page\n");
    $p_expected = array(
      "<!DOCTYPE html>",
      "<html>",
      "<head>",
      "  <title>Modify record</title>",
      "  <link rel='stylesheet' type='text/css' href='default_style.css'>",
      "  <meta charset='utf-8'>",
      "</head>",
      "<body>",
      "  <header>",
      "    <div id='titlebar'>",
      "      <div id='title'>Alvarezp</div>",
      "      <div id='title-icons'>",
      "        <div id='reports'>",
      "          <a href='#' title='Reports (not working yet)'><img src='/images/Text-x-generic.svg'></a>",
      "        </div>",
      "        <div id='settings'>",
      "          <a href='#' title='Settings (not working yet)'><img src='/images/Emblem-system.svg'></a>",
      "        </div>",
      "        <div id='notifications'>",
      "          <a href='#' title='Notifications (not working yet)'><img src='/images/Mail-closed.svg'></a>",
      "        </div>",
      "      </div>",
      "    </div>",
      "    <nav id='menubar'>",
      "      <a class='Entity' href='archives.html' title='TITLE'>Archives</a>",
      "    </nav>",
      "  </header>",
      "</body>",
      "</html>"
    );
    $p = new qb_widget_page("Alvarezp", 'Modify record');
    $p->add_menu_link('Archives', 'archives.html', 'TITLE', array('class' => "Entity"));
    $result = $p->get_html_array();
    if (!assert($result == $p_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $p_expected)); $n ++) {
            if ($result[$n] != $p_expected[$n]) {
                print("Check out line $n\n");
            }
        }
        exit;
    }

    require_once("html_element.php");

    print("Test qb-page-with-paragraph\n");
    $p_expected = array(
      "<!DOCTYPE html>",
      "<html id='page-id'>",
      "<head>",
      "  <title>Some paragraph</title>",
      "  <link rel='stylesheet' type='text/css' href='default_style.css'>",
      "  <meta charset='utf-8'>",
      "</head>",
      "<body>",
      "  <header>",
      "    <div id='titlebar'>",
      "      <div id='title'>Alvarezp</div>",
      "      <div id='title-icons'>",
      "        <div id='reports'>",
      "          <a href='#' title='Reports (not working yet)'><img src='/images/Text-x-generic.svg'></a>",
      "        </div>",
      "        <div id='settings'>",
      "          <a href='#' title='Settings (not working yet)'><img src='/images/Emblem-system.svg'></a>",
      "        </div>",
      "        <div id='notifications'>",
      "          <a href='#' title='Notifications (not working yet)'><img src='/images/Mail-closed.svg'></a>",
      "        </div>",
      "      </div>",
      "    </div>",
      "  </header>",
      "  <p class='classy'>",
      "    Hola",
      "  </p>",
      "</body>",
      "</html>"
    );
    $p = new qb_widget_page("Alvarezp", "Some paragraph", array('id' => 'page-id'));
    $p->add_child(new qb_html_element('p', array('class' => 'classy'), "Hola"));
    $result = $p->get_html_array();
    if (!assert($result == $p_expected)) {
        print_r($result);
        for ($n = 0; $n < count(min($result, $p_expected)); $n ++) {
            if ($result[$n] != $p_expected[$n]) {
                print("Check out line $n\n");
            }
        }
    }

