<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    // Site key.
    $setting = new admin_setting_configtext(
        'captcha_geetest/sitekey',
        new lang_string('sitekey', 'captcha_hcaptcha'),
        new lang_string('sitekey_desc', 'captcha_hcaptcha'),
        '',
        PARAM_NOTAGS,
    );
    $setting->set_force_ltr(true);
    $settings->add($setting);
    $setting = new admin_setting_configtext(
        'captcha_geetest/privatekey',
        new lang_string('privatekey', 'captcha_hcaptcha'),
        new lang_string('privatekey_desc', 'captcha_hcaptcha'),
        '',
        PARAM_NOTAGS,
    );
    $setting->set_force_ltr(true);
    $settings->add($setting);
}
