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
 * @module     tool_moodlenet/share
 * @copyright  2022 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.1
 */

import Config from 'core/config';
import {displayException, addNotification} from 'core/notification';
import ModalFactory from 'core/modal_factory';
import Prefetch from 'core/prefetch';
import * as Templates from 'core/templates';
import ShareModalActivity from "tool_moodlenet/share_modal_activity";
import * as MoodleNetSelectors from 'tool_moodlenet/selectors';
import * as MoodleNetRepository from 'tool_moodlenet/repository';

let currentModal;
let siteSupportUrl;

/**
 * Handle send to MoodleNet
 * @param {boolean} status
 */
const sendToMoodleNet = (status) => {
    const $modal = currentModal.getModal();
    const modal = $modal[0];
    modal.querySelector('.modal-header').classList.remove('no-border');

    currentModal.setBody(Templates.render('tool_moodlenet/share_modal_content_packaging', {}));
    currentModal.hideFooter();

    setTimeout(() => {
        moodleNetDone(status);
    }, 5000);
};

/**
 * Handle done status.
 * @param {boolean} status
 */
const moodleNetDone = (status) => {
    const $modal = currentModal.getModal();
    const modal = $modal[0];
    modal.querySelector('.modal-header').classList.add('hidden');
    modal.querySelector('.modal-body').classList.add('pt-0');
    currentModal.setBody(Templates.render('tool_moodlenet/share_modal_content_done', {
        success: status,
        sitesupporturl: siteSupportUrl,
    }));
};

/**
 * Register events.
 */
const registerEventListeners = () => {
    document.addEventListener('click', e => {
        const shareAction = e.target.closest(MoodleNetSelectors.action.share);
        const shareToSuccessAction = e.target.closest('[data-action="share-success"]');
        const shareToFailAction = e.target.closest('[data-action="share-fail"]');
        if (shareAction) {
            e.preventDefault();
            const type = shareAction.getAttribute('data-type');
            if (type == 'activity') {
                const cmId = Config.contextInstanceId;
                MoodleNetRepository.getActivityInformation(cmId).then((data) => {
                    if (data.status) {
                        siteSupportUrl = data.supportpageurl;
                        const modalPromise = ModalFactory.create({
                            type: ShareModalActivity.TYPE,
                            large: true,
                            templateContext: {
                                'activitytype': data.type,
                                'activityname': data.name,
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

        if (shareToSuccessAction) {
            e.preventDefault();
            sendToMoodleNet(true);
        }
        if (shareToFailAction) {
            e.preventDefault();
            sendToMoodleNet(false);
        }
    });
};

/**
 * Initialises.
 */
export const init = () => {
    Prefetch.prefetchTemplates([
        'tool_moodlenet/share_modal_activity',
        'tool_moodlenet/share_modal_content_done',
        'tool_moodlenet/share_modal_content_packaging',
    ]);
    registerEventListeners();
};
