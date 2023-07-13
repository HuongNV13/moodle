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
 * MoodleNet mutations.
 * An instance of this class will be used to add custom mutations to the course editor.
 *
 * @module     core/moodlenet/mutations
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.2
 */

import DefaultMutations from 'core_courseformat/local/courseeditor/mutations';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import CourseActions from 'core_courseformat/local/content/actions';
import * as MoodleNetService from 'core/moodlenet/service';
import Config from 'core/config';
import {exception as displayException} from 'core/notification';
import ModalFactory from 'core/modal_factory';
import SendActivityModal from 'core/moodlenet/send_activity_modal';
import {get_string as getString} from 'core/str';

class MoodleNetMutations extends DefaultMutations {

    /**
     * Share to MoodleNet.
     *
     * @param {StateManager} stateManager the current state manager
     */
    shareToMoodleNet = async function(stateManager) {
        const course = stateManager.get('course');
        window.console.log(course.id, stateManager.state.bulk.selection);

        const resourceId = Config.contextInstanceId;
        const infoPromise = MoodleNetService.getCourseInformation(resourceId);
        infoPromise.then(async(data) => {
            const modalPromise = ModalFactory.create({
                type: SendActivityModal.TYPE,
                large: true,
                templateContext: {
                    'activitytype': data.type,
                    'activityname': data.name,
                    'sharetype': await getString('moodlenet:sharetype' + 'course', 'moodle'),
                    'server': data.server,
                }
            });
            return modalPromise.then(modal => {
                modal.show();
                return modal;
            }).catch(displayException);
        }).catch(displayException);
    };
}

/**
 * Initialize.
 */
export const init = () => {
    const courseEditor = getCurrentCourseEditor();
    courseEditor.addMutations(new MoodleNetMutations());
    // Add direct mutation content actions.
    CourseActions.addActions({
        cmShareToMoodleNet: 'shareToMoodleNet'
    });
};
