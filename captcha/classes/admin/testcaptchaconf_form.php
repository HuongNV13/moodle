<?php

namespace core_captcha\admin;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
class testcaptchaconf_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;
    }
}
