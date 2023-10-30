<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    // Site key.
    $setting = new admin_setting_configtext(
        'captcha_mtcaptcha/sitekey',
        new lang_string('sitekey', 'captcha_mtcaptcha'),
        new lang_string('sitekey_desc', 'captcha_mtcaptcha'),
        '',
        PARAM_NOTAGS,
    );
    $setting->set_force_ltr(true);
    $settings->add($setting);
    $setting = new admin_setting_configtext(
        'captcha_mtcaptcha/privatekey',
        new lang_string('privatekey', 'captcha_mtcaptcha'),
        new lang_string('privatekey_desc', 'captcha_mtcaptcha'),
        '',
        PARAM_NOTAGS,
    );
    $setting->set_force_ltr(true);
    $settings->add($setting);
}
