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
 * Javascript popover for the `core_calendar` subsystem.
 *
 * @module core_calendar/popover
 * @copyright 2021 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since 4.0
 */

// eslint-disable-next-line no-unused-vars
import Popover from 'theme_boost/popover';
import jQuery from 'jquery';
import {eventTypes} from 'core_block/events';

let blockNode = null;

/**
 * Init the popover init for calendar date.
 * @param {NodeList} dates List of date element to enable
 */
const initPopover = (dates) => {
    dates.forEach((date) => {
        let dateEle = jQuery(date);
        dateEle.popover({
            trigger: 'hover focus',
            placement: 'top',
            html: true,
            content: function() {
                let source = dateEle.next('div[data-region="day-content"]');
                let content = jQuery('<div>');
                if (source.length) {
                    let temptContent = source.find('.hidden').clone(false);
                    content.html(temptContent.html());
                }
                return content.html();
            }
        });
    });
};

/**
 * Enable the popover for calendar date.
 * @param {NodeList} dates List of date element to enable
 */
const enablePopover = (dates) => {
    dates.forEach((date) => {
        let dateEle = jQuery(date);
        dateEle.popover('enable');
    });
};

/**
 * Disable the popover for calendar date.
 * @param {NodeList} dates List of date element to enable
 */
const disablePopover = (dates) => {
    dates.forEach((date) => {
        let dateEle = jQuery(date);
        dateEle.popover('disable');
    });
};

/**
 * Check if we are allowing to enable the popover or not.
 *
 * @returns {boolean}
 */
const isPopoverAvailable = () => {
    let isAvailable = false;
    let dayContents = blockNode.querySelectorAll('.d-md-block [data-region="day-content"]');
    dayContents.forEach((dayContent) => {
        if (window.getComputedStyle(dayContent).display === 'none') {
            isAvailable = true;
        }
    });
    return isAvailable;
};

/**
 * Popover event handler
 * @param {NodeList} dates List of date element
 */
const handlePopover = (dates) => {
    if (isPopoverAvailable()) {
        enablePopover(dates);
    } else {
        disablePopover(dates);
    }
};

/**
 * Initialises popover.
 *
 * @param {String} instanceId Form element
 * @listens event:blockMoved
 * @listens event:resize
 */
export const init = (instanceId) => {
    blockNode = document.querySelector('[data-instance-id="' + instanceId + '"]');
    const dates = blockNode.querySelectorAll('.d-md-block [data-action="view-day-link"]');
    initPopover(dates);
    if (!isPopoverAvailable()) {
        disablePopover(dates);
    }

    document.addEventListener(eventTypes.blockMoved, function(e) {
        if (e.detail.instanceId === instanceId) {
            handlePopover(dates);
        }
    });

    window.addEventListener('resize', function() {
        handlePopover(dates);
    });
};