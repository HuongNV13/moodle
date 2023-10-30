<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    // Site key.
    $setting = new admin_setting_configtext(
        'captcha_recaptchav2/sitekey',
        new lang_string('sitekey', 'captcha_recaptchav2'),
        new lang_string('sitekey_desc', 'captcha_recaptchav2'),
        '',
        PARAM_NOTAGS,
    );
    $setting->set_force_ltr(true);
    $settings->add($setting);
    $setting = new admin_setting_configtext(
        'captcha_recaptchav2/privatekey',
        new lang_string('privatekey', 'captcha_recaptchav2'),
        new lang_string('privatekey_desc', 'captcha_recaptchav2'),
        '',
        PARAM_NOTAGS,
    );
    $setting->set_force_ltr(true);
    $settings->add($setting);
}
