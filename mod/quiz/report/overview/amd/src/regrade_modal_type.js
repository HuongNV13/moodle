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
 * Regrade modal type is used to regrade or dryrun the attempts and questions.
 *
 * @module     quiz_overview/regrade_modal_type
 * @copyright  2024 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.5
 */

import Modal from 'core/modal';

export default class RegradeModal extends Modal {
    static TYPE = 'quiz_overview/regrade_modal';
    static TEMPLATE = 'quiz_overview/regrade_modal_base';

    registerEventListeners() {
        // Call the parent registration.
        super.registerEventListeners();

        // Register to close on save/cancel.
        this.registerCloseOnSave();
        this.registerCloseOnCancel();
    }

    configure(modalConfig) {
        modalConfig.removeOnClose = true;
        modalConfig.isVerticallyCentered = true;
        super.configure(modalConfig);
    }
}

RegradeModal.registerModalType();
