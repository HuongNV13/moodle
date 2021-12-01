<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for editing steps.
 *
 * @package    tool_usertours
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_usertours\local\forms;

use core_plugin_manager;
use tool_usertours\helper;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing steps.
 *
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editstep extends \moodleform {
    /**
     * @var tool_usertours\step $step
     */
    protected $step;

    /**
     * Create the edit step form.
     *
     * @param   string      $target     The target of the form.
     * @param   step        $step       The step being editted.
     */
    public function __construct($target, \tool_usertours\step $step) {
        $this->step = $step;

        parent::__construct($target);
    }

    /**
     * Form definition.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'heading_target', get_string('target_heading', 'tool_usertours'));
        $types = [];
        foreach (\tool_usertours\target::get_target_types() as $value => $type) {
            $types[$value] = get_string('target_' . $type, 'tool_usertours');
        }
        $mform->addElement('select', 'targettype', get_string('targettype', 'tool_usertours'), $types);
        $mform->addHelpButton('targettype', 'targettype', 'tool_usertours');

        // The target configuration.
        foreach (\tool_usertours\target::get_target_types() as $value => $type) {
            $targetclass = \tool_usertours\target::get_classname($type);
            $targetclass::add_config_to_form($mform);
        }

        // Content of the step.
        $mform->addElement('header', 'heading_content', get_string('content_heading', 'tool_usertours'));
        $mform->addElement('textarea', 'title', get_string('title', 'tool_usertours'));
        $mform->addRule('title', get_string('required'), 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'title', 'tool_usertours');

        // Content type.
        $typeoptions = [
            helper::TOOL_USERTOURS_CONTENTTYPE_LANGSTRING => get_string('content_type_langstring', 'tool_usertours'),
            helper::TOOL_USERTOURS_CONTENTTYPE_HTML => get_string('content_type_html', 'tool_usertours')
        ];
        $mform->addElement('select', 'contenttype', get_string('content_type', 'tool_usertours'), $typeoptions);
        $mform->addHelpButton('contenttype', 'content_type', 'tool_usertours');
        $mform->setDefault('contenttype', helper::TOOL_USERTOURS_CONTENTTYPE_HTML);

        // Language identifier.
        $mform->addElement('text', 'contentlangstring', get_string('language_identifider', 'tool_usertours'));
        $mform->setType('contentlangstring', PARAM_TEXT);
        $mform->hideIf('contentlangstring', 'contenttype', 'eq', helper::TOOL_USERTOURS_CONTENTTYPE_HTML);

        // Component.
        $mform->addElement('autocomplete', 'component', get_string('component', 'tool_usertours'), $this->get_components_list());
        $mform->hideIf('component', 'contenttype', 'eq', helper::TOOL_USERTOURS_CONTENTTYPE_HTML);

        // HTML content.
        $editoroptions = [
            'subdirs' => 1,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'changeformat' => false,
            'trusttext' => true
        ];
        $objs = $mform->createElement('editor', 'content', get_string('content', 'tool_usertours'), null, $editoroptions);
        // TODO: MDL-68540 We need to add the editor to a group element because editor element will not work with hideIf.
        $mform->addElement('group', 'contenthtmlgrp', get_string('content', 'tool_usertours'), [$objs], ' ', false);
        $mform->addHelpButton('contenthtmlgrp', 'content', 'tool_usertours');
        $mform->hideIf('contenthtmlgrp', 'contenttype', 'eq', helper::TOOL_USERTOURS_CONTENTTYPE_LANGSTRING);

        // Add the step configuration.
        $mform->addElement('header', 'heading_options', get_string('options_heading', 'tool_usertours'));
        // All step configuration is defined in the step.
        $this->step->add_config_to_form($mform);

        // And apply any form constraints.
        foreach (\tool_usertours\target::get_target_types() as $value => $type) {
            $targetclass = \tool_usertours\target::get_classname($type);
            $targetclass::add_disabled_constraints_to_form($mform);
        }

        $this->add_action_buttons();
    }

    /**
     * Get the available components list.
     *
     * @return array
     */
    private function get_components_list(): array {
        $componentslist = [];
        $componentslist[''] = '';
        $pluginman = core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugins();
        foreach ($plugininfo as $plugins) {
            foreach ($plugins as $plugin) {
                $componentslist[$plugin->type . '_' . $plugin->name] = get_string('component_name', 'tool_usertours', $plugin);
            }
        }

        return $componentslist;
    }

    /**
     * Validate the data base on the submitted content type.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if ($data['contenttype'] == helper::TOOL_USERTOURS_CONTENTTYPE_LANGSTRING) {
            $langid = '';
            $langcomponent = '';
            if (!isset($data['contentlangstring']) || trim($data['contentlangstring']) == '') {
                $errors['contentlangstring'] = get_string('required');
            } else {
                $langid = trim($data['contentlangstring']);
            }
            if (!isset($data['component']) || trim($data['component']) == '') {
                $errors['component'] = get_string('required');
            } else {
                $langcomponent = trim($data['component']);
            }
            if (!empty($langid) && !empty($langcomponent)) {
                if (!get_string_manager()->string_exists($langid, $langcomponent)) {
                    $errors['contentlangstring'] = get_string('invalid_lang_id', 'tool_usertours');
                }
            }
        }
        if ($data['contenttype'] == helper::TOOL_USERTOURS_CONTENTTYPE_HTML) {
            if (strip_tags($data['content']['text']) == '') {
                $errors['content'] = get_string('required');
            }
        }

        return $errors;
    }

    /**
     * After definition hook.
     * Check if the content type is language or not and set the correct value to the form.
     *
     * @return void
     */
    protected function after_definition(): void {
        parent::after_definition();
        $mform = $this->_form;

        if ($this->step->get_contenttype() == helper::TOOL_USERTOURS_CONTENTTYPE_LANGSTRING) {
            if (!empty($this->step->get_content())) {
                $bit = explode(',', $this->step->get_content());
                $contentlangstringele = $mform->getElement('contentlangstring');
                $componentele = $mform->getElement('component');
                $contentlangstringele->setValue($bit[0]);
                $componentele->setValue($bit[1]);
            }
        }
    }

}
