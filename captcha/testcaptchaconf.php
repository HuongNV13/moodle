<?php
require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');

// This is an admin page.
admin_externalpage_setup('testoutgoingmailconf');

$headingtitle = 'Test captcha configuration';
$returnurl = new moodle_url('/captcha/testcaptchaconf.php');

echo $OUTPUT->header();
echo $OUTPUT->heading($headingtitle);

$providertype = get_config('captcha', 'provider');
$provider = new \core_captcha\provider($providertype);

$form = html_writer::start_tag('form', ['method' => 'POST', 'action' => $returnurl]);
$form .= html_writer::div($provider->get_output_html(), 'pb-2 pt-2');
$form .= $button = html_writer::tag('button', 'Submit', [
    'name' => 'submit',
    'class' => 'btn btn-primary',
    'type' => 'submit',
]);
$form .= html_writer::end_tag('form');

if (isset($_POST['submit'])) {
    switch ($providertype) {
        case 'captcha_recaptchav2':
        case 'captcha_hcaptcha':
            $check = $provider->verify_response($_POST['g-recaptcha-response']);
            break;
        case 'captcha_mtcaptcha':
            $check = $provider->verify_response($_POST['mtcaptcha-verifiedtoken']);
            break;
        case 'captcha_geetest':
            $check = $provider->verify_response($_POST['mtcaptcha-verifiedtoken']);
            break;
        default:
            $check = ['isvalid' => false, 'error' => ['Unknown provider']];
    }
    $result = html_writer::start_div();
    $result .= html_writer::div('Valid: ' . ($check['isvalid'] ? 'Yes' : 'No'));
    if (!empty($check['error'])) {
        $result .= html_writer::div('Errors: ' . $check['error'][0]);
    }
    $result .= html_writer::end_div();
    echo $result;
} else {
    echo $form;
}

echo $OUTPUT->footer();
