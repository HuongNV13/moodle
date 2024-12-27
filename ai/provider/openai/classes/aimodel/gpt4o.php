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

namespace aiprovider_openai\aimodel;

use core_ai\aimodel\base;
use MoodleQuickForm;

/**
 * GPT-4o AI model.
 *
 * @package    xxx_yyy
 * @copyright  2024 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gpt4o extends base {

    #[\Override]
    public function get_model_name(): string {
        return 'gpt-4o';
    }

    #[\Override]
    public function get_model_display_name(): string {
        return 'GPT-4o';
    }

    #[\Override]
    public function add_model_settings(MoodleQuickForm $mform): void {
        $mform->addElement(
            'text',
            'model_frequency_penalty',
            'Frequency penalty'
        );
        $mform->setType('model_frequency_penalty', PARAM_TEXT);

        $mform->addElement(
            'text',
            'model_logit_bias',
            'Logit bias'
        );
        $mform->setType('model_logit_bias', PARAM_TEXT);

        $mform->addElement(
            'text',
            'model_max_completion_tokens',
            'Max completion tokens'
        );
        $mform->setType('model_max_completion_tokens', PARAM_TEXT);

        $mform->addElement(
            'text',
            'model_temperature',
            'Temperature'
        );
        $mform->setType('model_temperature', PARAM_TEXT);
    }
}
