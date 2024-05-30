<?php

require_once('config.php');
require_once($CFG->dirroot.'/lib/formslib.php');

class test_form extends moodleform {

    function definition() {

        $mform = $this->_form;

        // Radio buttons.
        $radiogroup = [
            $mform->createElement('radio', 'some_radios', '', 'enable', '1'),
            $mform->createElement('radio', 'some_radios', '', 'disable', '2'),
            $mform->createElement('radio', 'some_radios', '', 'other', '3'),
        ];

        $mform->addGroup($radiogroup, 'some_radios_group', 'Enable/Disable', ' ', false);
        $mform->setDefault('some_radios', 2);

        // Editor
        $mform->addElement('editor', 'some_editor', 'My editor');
        $mform->hideIf('some_editor', 'some_radios', 'neq', '1');

        // Test field
        $mform->addElement('text', 'some_text', 'My text');
        $mform->hideIf('some_text', 'some_radios', 'neq', '1');

        $mform->addElement('editor', 'some_editor2', 'My editor2');

        $this->add_action_buttons();

        // End of: radio mform element.

        // $elementlabel = 'Upload your CV';
        // $filemanagerbasename = 'myfilemanager_filemanager';
        // $filemanagerbaseid = $filemanagerbasename.'id';

        // $filetypes = array_map('trim', explode(',', '.odt, .pdf'));

        // $attributes = [];
        // $attributes['id'] = $filemanagerbaseid;
        // $attributes['maxbytes'] = '1024';
        // $attributes['accepted_types'] = $filetypes;
        // $attributes['subdirs'] = false;
        // $attributes['maxfiles'] = 3;

        // $usefilemanagergroup = true;
        // if (!$usefilemanagergroup) {
            // Begin of: filemanager mform element.
        //$mform->addElement('filemanager', $filemanagerbasename, $elementlabel, null, $attributes);
        //$mform->disabledIf($filemanagerbasename, $booleanbasename, 'neq', '1');


        // } else {
        //     // Begin of: filemanager mform element.
        //     //$elementgroup = [];

        //     $mform->createElement('filemanager', $filemanagerbasename, $elementlabel, null, $attributes);

        //     //$mform->addGroup($elementgroup, $filemanagerbasename.'_group', $elementlabel, ' ', false);

        //     $mform->disabledIf($filemanagerbasename, $booleanbasename, 'neq', '1');
        //     // $mform->disabledIf($filemanagerbasename.'_group', $booleanbasename, 'neq', '1');
        //     // End of: filemanager mform element.
        //}

    }
}

// ===================
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/testmform.php');
$PAGE->set_title('testmform');

$mformurl = new moodle_url('/testmform.php');

$mform = new test_form($mformurl);

if ($mform->is_cancelled()) {
}

if ($data = $mform->get_data()) {
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
