
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
 * A module to handle Share operations of the MoodleNet.
 *
 * @module     core/moodlenet/send_activity
 * @copyright  2023 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.2
 */

import Config from 'core/config';
import ModalFactory from 'core/modal_factory';
import {displayException, addNotification} from 'core/notification';
import {get_string as getString} from 'core/str';
import Prefetch from "core/prefetch";
import * as Templates from 'core/templates';
import * as MoodleNetRepository from 'core/moodlenet/repository';
import SendActivityModal from 'core/moodlenet/send_activity_modal';

const TYPE_ACTIVITY = "activity";

let currentModal;
// eslint-disable-next-line no-unused-vars
let siteSupportUrl;
let issuerId;

/**
 * Handle send to MoodleNet
 */
const sendToMoodleNet = () => {
    const $modal = currentModal.getModal();
    const modal = $modal[0];
    modal.querySelector('.modal-header').classList.remove('no-border');
    modal.querySelector('.modal-header').classList.add('no-header-text');

    currentModal.setBody(Templates.render('core/moodlenet/send_activity_modal_packaging', {}));
    currentModal.hideFooter();
};

/**
 * Handle response from MoodleNet.
 * @param {boolean} status
 * @param {String} resourceUrl
 */
const responseFromMoodleNet = (status, resourceUrl = '') => {
    const $modal = currentModal.getModal();
    const modal = $modal[0];
    modal.querySelector('.modal-header').classList.add('no-border');
    currentModal.setBody(Templates.render('core/moodlenet/send_activity_modal_done', {
        success: status,
        sitesupporturl: siteSupportUrl,
    }));

    if (status) {
        currentModal.setFooter(Templates.render('core/moodlenet/send_activity_modal_footer_view', {
            resourseurl: resourceUrl,
        }));
        currentModal.showFooter();
    }
};

/**
 * Register events.
 */
const registerEventListeners = () => {
    document.addEventListener('click', e => {
        const shareAction = e.target.closest('[data-action="sendtomoodlenet"]');
        const sendAction = e.target.closest('.moodlenet-action-buttons [data-action="share"]');
        const successAction = e.target.closest('.moodlenet-share-modal-content .loading-icon');
        const failAction = e.target.closest('.moodlenet-share-modal-content .test-share-large');
        if (shareAction) {
            e.preventDefault();
            const type = shareAction.getAttribute('data-type');
            const shareType = shareAction.getAttribute('data-sharetype');
            const cmId = Config.contextInstanceId;
            if (type == TYPE_ACTIVITY) {
                MoodleNetRepository.getActivityInformation(cmId).then(async(data) => {
                    if (data.status) {
                        siteSupportUrl = data.supportpageurl;
                        issuerId = data.issuerid;
                        const modalPromise = ModalFactory.create({
                            type: SendActivityModal.TYPE,
                            large: true,
                            templateContext: {
                                'activitytype': data.type,
                                'activityname': data.name,
                                'sharetype': await getString('share_type_' + shareType, 'core_moodlenet'),
                                'server': data.server,
                            }
                        });
                        return modalPromise.then(modal => {
                            currentModal = modal;
                            modal.show();
                            return modal;
                        }).catch(displayException);
                    } else {
                        return addNotification({
                            message: data.warnings[0].message,
                            type: 'error'
                        });
                    }
                }).catch(displayException);
            }
        }

        if (sendAction) {
            e.preventDefault();
            sendToMoodleNet();
        }

        if (successAction) {
            const courseId = Config.courseId;
            const cmId = Config.contextInstanceId;
            const shareFormat = 0;
            MoodleNetRepository.sendActivity(issuerId, courseId, cmId, shareFormat).then(async(data) => {
                const status = data.status;
                const resourceUrl = data.resourceurl;
                responseFromMoodleNet(status, resourceUrl);
            }).catch(displayException);
        }

        if (failAction) {
            responseFromMoodleNet(false);
        }
    });
};

export const init = () => {
    Prefetch.prefetchTemplates([
        'core/moodlenet/send_activity_modal_base',
    ]);
    registerEventListeners();
};
