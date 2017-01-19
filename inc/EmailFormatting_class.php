<?php

class EmailFormatting {

    private $message = array();
    private $data = array();
    private $type = '';
    private $formatted = false;

    public function __construct($format) {
        $this->type = $format;
    }

    public function set_title($title) {
        $this->data['title'] = $title;
        return $this;
    }

    public function set_message($message) {
        $this->data['message'] = $message;
        return $this;
    }

    public function add_link($title, $url) {
        $this->data['links'][] = array('title' => $title, 'url' => $url);
    }

    private function parse_links() {
        global $template;

        $buttons = '';
        if (is_array($this->data['links'])) {
            foreach ($this->data['links'] as $sequence => $link) {
                eval("\$buttons .= \"" . $template->get('emailfomatting_default_button') . "\";");
            }
        }
        return $buttons;
    }

    public function format() {
        global $core, $template;

        $messagedata = $this->data;

        $buttons = $this->parse_links();
        switch ($this->type) {
            case 'standard':
                $path = '';
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                eval("\$outputmessage = \"" . $template->get('emailfomatting_default') . "\";");
                break;
            default:
                $outputmessage = $this->data['message'];
                break;
        }
        $this->message = $outputmessage;
        $this->formatted = true;
    }

    public function get_message() {
        if ($this->formatted == false) {
            $this->format();
        }
        return $this->message;
    }

}
