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

namespace aiprovider_openai\form;

use aiprovider_openai\helper;
use core_ai\form\action_settings_form;

/**
 * Base action settings form for OpenAI provider.
 *
 * @package    aiprovider_openai
 * @copyright  2024 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_form extends action_settings_form {
    /**
     * @var array Action configuration.
     */
    protected $actionconfig;
    /**
     * @var string Return URL.
     */
    protected $returnurl;
    /**
     * @var string Action name.
     */
    protected $actionname;
    /**
     * @var string Action class.
     */
    protected $action;
    /**
     * @var int Provider ID.
     */
    protected $providerid;
    /**
     * @var string Provider name.
     */
    protected $providername;

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $this->actionconfig = $this->_customdata['actionconfig']['settings'] ?? [];
        $this->returnurl = $this->_customdata['returnurl'] ?? null;
        $this->actionname = $this->_customdata['actionname'];
        $this->action = $this->_customdata['action'];
        $this->providerid = $this->_customdata['providerid'] ?? 0;
        $this->providername = $this->_customdata['providername'] ?? 'aiprovider_openai';

        $mform->addElement('header', 'generalsettingsheader', 'General');
    }

    #[\Override]
    function get_data() {
        $data = parent::get_data();
        if ($data && isset($data->modeltemplate)) {
            $data->model = $data->modeltemplate;
            unset($data->modeltemplate);
        }

        return $data;
    }

    protected function add_model_fields(): void {
        global $PAGE;
        $PAGE->requires->js_call_amd('aiprovider_openai/modelchooser', 'init');
        $mform = $this->_form;

        // Action model to use.
        $mform->addElement(
            'select',
            'modeltemplate',
            get_string("action:{$this->actionname}:model", 'aiprovider_openai'),
            $this->get_model_list(),
            ['data-modelchooser-field' => 'selector'],
        );
        $mform->setType('modeltemplate', PARAM_TEXT);
        $mform->addRule('modeltemplate', null, 'required', null, 'client');
        $mform->setDefault('modeltemplate', $this->actionconfig['model'] ?? 'gpt-4o');
        $mform->addHelpButton('modeltemplate', "action:{$this->actionname}:model", 'aiprovider_openai');

        $mform->addElement(
            'text',
            'model',
            'Test model'
        );
        $mform->setType('model', PARAM_TEXT);

        $mform->registerNoSubmitButton('updateactionsettings');
        $mform->addElement(
            'submit',
            'updateactionsettings',
            'update Action settings',
            ['data-modelchooser-field' => 'updateButton', 'class' => 'd-none']
        );
    }

    /**
     * Get the list of models.
     *
     * @return array List of models.
     */
    private function get_model_list(): array {
        $models = [];
        $models['custom'] = 'Custom';
        foreach (helper::get_model_classes() as $class) {
            $model = new $class();
            $models[$model->get_model_name()] = $model->get_model_display_name();
        }
        return $models;
    }
}
