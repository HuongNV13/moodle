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
 * Question class for drag and drop marker question type, used to support the question and preview pages.
 *
 * @package    qtype_ddmarker
 * @subpackage question
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// eslint-disable-next-line no-unused-vars
define(['jquery', 'core/dragdrop', 'qtype_ddmarker/shapes', 'core/key_codes'], function($, dragDrop, Shapes, keys) {

    "use strict";

    /**
     * Object to handle one drag-drop markers question.
     *
     * @param {String} containerId id of the outer div for this question.
     * @param {boolean} readOnly whether the question is being displayed read-only.
     * @param {Object[]} visibleDropZones the geometry of any drop-zones to show.
     *      Objects have fields shape, coords and markertext.
     * @constructor
     */
    function DragDropMarkersQuestion(containerId, readOnly, visibleDropZones) {
        this.containerId = containerId;
        this.visibleDropZones = visibleDropZones;
        if (readOnly) {
            this.getRoot().addClass('qtype_ddmarker-readonly');
        }
        this.cloneDrags();
        this.repositionDrags();
        this.drawDropzones();
    }

    DragDropMarkersQuestion.prototype.cloneDrags = function() {
        var thisQ = this;
        thisQ.getRoot().find('div.draghomes span.marker').each(function(index, draghome) {
            var drag = $(draghome);
            var placeHolder = drag.clone();
            placeHolder.removeClass();
            placeHolder.addClass('marker choice' + thisQ.getChoice(drag) + ' dragno' + thisQ.getDragNo(drag) + ' dragplaceholder');
            drag.before(placeHolder);
        });
    };

    /**
     * Get the choice number of a drag.
     *
     * @param {jQuery} drag the drag.
     * @returns {Number} the choice number.
     */
    DragDropMarkersQuestion.prototype.getChoice = function(drag) {
        return this.getClassnameNumericSuffix(drag, 'choice');
    };

    /**
     * Get the drag number of a drag.
     *
     * @param {jQuery} drag the drag.
     * @returns {Number} the drag number.
     */
    DragDropMarkersQuestion.prototype.getDragNo = function(drag) {
        return this.getClassnameNumericSuffix(drag, 'dragno');
    };

    DragDropMarkersQuestion.prototype.handleDragStart = function(e) {
        var thisQ = this,
            dragged = $(e.target).closest('.marker');

        var info = dragDrop.prepare(e);
        if (!info.start) {
            return;
        }

        dragged.addClass('beingdragged');

        var placed = !dragged.hasClass('unneeded');
        if (!placed) {
            var hiddenDrag = thisQ.getDragClone(dragged);
            if (hiddenDrag.length) {
                hiddenDrag.addClass('active');
                dragged.offset(hiddenDrag.offset());
            }
        }

        dragDrop.start(e, dragged, function() {
            void(1);
        }, function(x, y, dragged) {
            thisQ.dragEnd(dragged);
        });
    };

    /**
     * Functionality at the end of a drag drop.
     * @param {jQuery} dragged the marker that was dragged.
     */
    DragDropMarkersQuestion.prototype.dragEnd = function(dragged) {
        var placed = false,
            choiceNo = this.getChoiceNoFromElement(dragged);

        dragged.data('pagex', dragged.offset().left).data('pagey', dragged.offset().top);
        if (this.coordsInBgImg(new Shapes.Point(dragged.data('pagex'), dragged.data('pagey')))) {
            this.sendDragToDrop(dragged);
            placed = true;
        }

        if (!placed) {
            this.sendDragHome(dragged);
            this.removeDragIfNeeded(dragged);
        } else {
            this.cloneDragIfNeeded(dragged);
        }

        this.saveCoordsForChoice(choiceNo, dragged);
    };

    /**
     * Save the coordinates for a dropped item in the form field.
     * @param {Number} choiceNo which copy of the choice this was.
     * @param {jQuery} dropped the choice that was dropped here.
     */
    // eslint-disable-next-line no-unused-vars
    DragDropMarkersQuestion.prototype.saveCoordsForChoice = function(choiceNo, dropped) {
        var coords = [],
            items = this.getRoot().find('span.marker.choice' + choiceNo),
            thiQ = this;

        if (items.length) {
            items.each(function() {
                var drag = $(this);
                if (!drag.hasClass('beingdragged')) {
                    var dragXY = new Shapes.Point(drag.data('pagex'), drag.data('pagey'));
                    if (thiQ.coordsInBgImg(dragXY)) {
                        coords[coords.length] = thiQ.convertToBgImgXY(dragXY);
                    }
                }
            });
        }

        this.getRoot().find('input.choice' + choiceNo).val(coords.join(';'));
    };

    /**
     * Returns the choice number for a node.
     *
     * @param {Element|jQuery} node
     * @returns {Number}
     */
    DragDropMarkersQuestion.prototype.getChoiceNoFromElement = function(node) {
        return Number(this.getClassnameNumericSuffix(node, 'choice'));
    };

    /**
     * Returns the numeric part of a class with the given prefix.
     *
     * @param {Element|jQuery} node
     * @param {String} prefix
     * @returns {Number|null}
     */
    DragDropMarkersQuestion.prototype.getClassnameNumericSuffix = function(node, prefix) {
        var classes = $(node).attr('class');
        if (classes !== undefined && classes !== '') {
            var classesarr = classes.split(' ');
            for (var index = 0; index < classesarr.length; index++) {
                var patt1 = new RegExp('^' + prefix + '([0-9])+$');
                if (patt1.test(classesarr[index])) {
                    var patt2 = new RegExp('([0-9])+$');
                    var match = patt2.exec(classesarr[index]);
                    return Number(match[0]);
                }
            }
        }
        return null;
    };

    /**
     * Get the outer div for this question.
     * @returns {jQuery} containing that div.
     */
    DragDropMarkersQuestion.prototype.getRoot = function() {
        return $(document.getElementById(this.containerId));
    };

    /**
     * Get the img that is the background image.
     * @returns {jQuery} containing that img.
     */
    DragDropMarkersQuestion.prototype.bgImage = function() {
        return this.getRoot().find('img.dropbackground');
    };

    /**
     * Utility function converting window coordinates to relative to the
     * background image coordinates.
     *
     * @param {Point} point relative to the page.
     * @returns {Point} point relative to the background image.
     */
    DragDropMarkersQuestion.prototype.convertToBgImgXY = function(point) {
        var bgImage = this.bgImage();
        return point.offset(-bgImage.offset().left - 1, -bgImage.offset().top - 1);
    };

    /**
     * Is the point within the background image?
     *
     * @param {Point} point relative to the BG image.
     * @return {boolean} true it they are.
     */
    DragDropMarkersQuestion.prototype.coordsInBgImg = function(point) {
        var bgImage = this.bgImage();
        var bgPossition = bgImage.offset();

        return point.x >= bgPossition.left && point.x < bgPossition.left + bgImage.width()
            && point.y >= bgPossition.top && point.y < bgPossition.top + bgImage.height();
    };

    /**
     * Draws the drag items on the page (and drop zones if required).
     * The idea is to re-draw all the drags and drops whenever there is a change
     * like a widow resize or an item dropped in place.
     */
    DragDropMarkersQuestion.prototype.repositionDrags = function() {
        var root = this.getRoot(),
            thisQ = this;

        root.find('div.draghomes .marker').not('.dragplaceholder').each(function(key, item) {
            $(item).addClass('unneeded');
        });

        root.find('input.choices').each(function(key, input) {
            var choiceNo = thisQ.getChoiceNoFromElement(input),
                coords = thisQ.getCoords(input);
            if (coords.length) {
                var drag = thisQ.getRoot().find('.draghomes' + ' span.marker' + '.choice' + choiceNo).not('.dragplaceholder');
                drag.remove();
                for (var i = 0; i < coords.length; i++) {
                    var dragInDrop = drag.clone();
                    dragInDrop.data('pagex', coords[i].x).data('pagey', coords[i].y);
                    thisQ.sendDragToDrop(dragInDrop);
                }
                thisQ.getDragClone(drag).addClass('active');
                thisQ.cloneDragIfNeeded(drag);
            }
        });
    };

    /**
     * Get drag clone for a given drag.
     *
     * @param {jQuery} drag the drag.
     * @returns {jQuery} the drag's clone.
     */
    DragDropMarkersQuestion.prototype.getDragClone = function(drag) {
        return this.getRoot().find('.draghomes' + ' span.marker' +
            '.choice' + this.getChoice(drag) + '.dragno' + this.getDragNo(drag) + '.dragplaceholder');
    };

    /**
     * Get the img that is the background image.
     * @returns {jQuery} droparea element.
     */
    DragDropMarkersQuestion.prototype.dropArea = function() {
        return this.getRoot().find('div.droparea');
    };

    /**
     * Animate a drag back to its home.
     *
     * @param {jQuery} drag the item being moved.
     */
    DragDropMarkersQuestion.prototype.sendDragHome = function(drag) {
        drag.removeClass('beingdragged')
            .addClass('unneeded')
            .css('top', '').css('left', '');
        var placeHolder = this.getDragClone(drag);
        placeHolder.after(drag);
        placeHolder.removeClass('active');
    };

    /**
     * Animate a drag item into a given place (or back home).
     *
     * @param {jQuery} drag the item to place.
     */
    DragDropMarkersQuestion.prototype.sendDragToDrop = function(drag) {
        var dropArea = this.dropArea();
        drag.removeClass('beingdragged').removeClass('unneeded');
        var dragXY = this.convertToBgImgXY(new Shapes.Point(drag.data('pagex'), drag.data('pagey')));
        drag.css('left', dragXY.x).css('top', dragXY.y);
        dropArea.append(drag);
    };

    /**
     * Clone the drag at the draghome area if needed.
     *
     * @param {jQuery} drag the item to place.
     */
    DragDropMarkersQuestion.prototype.cloneDragIfNeeded = function(drag) {
        var inputNode = this.getInput(drag),
            noOfDrags = Number(this.getClassnameNumericSuffix(inputNode, 'noofdrags')),
            displayedDragsInDropArea = this.getRoot().find('div.droparea .marker.choice' +
                this.getChoice(drag) + '.dragno' + this.getDragNo(drag)).length,
            displayedDragsInDragHomes = this.getRoot().find('div.draghomes .marker.choice' +
                this.getChoice(drag) + '.dragno' + this.getDragNo(drag)).not('.dragplaceholder').length;

        if (displayedDragsInDropArea < noOfDrags && displayedDragsInDragHomes === 0) {
            var dragclone = drag.clone();
            dragclone.addClass('unneeded')
                .css('top', '').css('left', '');
            this.getDragClone(drag)
                .removeClass('active')
                .after(dragclone);
        }
    };

    /**
     * Remove the clone drag at the draghome area if needed.
     *
     * @param {jQuery} drag the item to place.
     */
    DragDropMarkersQuestion.prototype.removeDragIfNeeded = function(drag) {
        var displayeddrags = this.getRoot().find('div.draghomes .marker.choice' +
            this.getChoice(drag) + '.dragno' + this.getDragNo(drag)).not('.dragplaceholder').length;
        if (displayeddrags > 1) {
            this.getRoot().find('div.draghomes .marker.choice' +
                this.getChoice(drag) + '.dragno' + this.getDragNo(drag)).not('.dragplaceholder').first().remove();
        }
    };

    /**
     * Get the input belong to drag.
     *
     * @param {jQuery} drag the item to place.
     * @returns {jQuery} input element.
     */
    DragDropMarkersQuestion.prototype.getInput = function(drag) {
        var choiceNo = this.getChoiceNoFromElement(drag);
        return this.getRoot().find('input.choices.choice' + choiceNo);
    };

    /**
     * Draws the svg shapes of any drop zones that should be visible for feedback purposes.
     */
    DragDropMarkersQuestion.prototype.drawDropzones = function() {
        if (this.visibleDropZones.length > 0) {
            var bgImage = this.bgImage();

            this.getRoot().find('div.dropzones').html('<svg xmlns="http://www.w3.org/2000/svg" class="dropzones" ' +
                'width="' + bgImage.outerWidth() + '" ' +
                'height="' + bgImage.outerHeight() + '"></svg>');
            var svg = this.getRoot().find('svg.dropzones');

            var nextColourIndex = 0;
            for (var dropZoneNo = 0; dropZoneNo < this.visibleDropZones.length; dropZoneNo++) {
                var colourClass = 'color' + nextColourIndex;
                nextColourIndex = (nextColourIndex + 1) % 8;
                this.addDropzone(svg, dropZoneNo, colourClass);
            }
        }
    };

    /**
     * Adds a dropzone shape with colour, coords and link provided to the array of shapes.
     *
     * @param {jQuery} svg the SVG image to which to add this drop zone.
     * @param {int} dropZoneNo which drop-zone to add.
     * @param {string} colourClass class name
     */
    DragDropMarkersQuestion.prototype.addDropzone = function(svg, dropZoneNo, colourClass) {
        var dropZone = this.visibleDropZones[dropZoneNo],
            shape = Shapes.make(dropZone.shape, ''),
            existingmarkertext;
        if (!shape.parse(dropZone.coords)) {
            return;
        }

        existingmarkertext = this.getRoot().find('div.markertexts span.markertext' + dropZoneNo);
        if (existingmarkertext.length) {
            if (dropZone.markertext !== '') {
                existingmarkertext.html(dropZone.markertext);
            } else {
                existingmarkertext.remove();
            }
        } else if (dropZone.markertext !== '') {
            var classnames = 'markertext markertext' + dropZoneNo;
            this.getRoot().find('div.markertexts').append('<span class="' + classnames + '">' +
                dropZone.markertext + '</span>');
            var markerspan = this.getRoot().find('div.ddarea div.markertexts span.markertext' + dropZoneNo);
            if (markerspan.length) {
                var handles = shape.getHandlePositions(),
                    textPos = this.convertToWindowXY(handles.moveHandle.offset(
                        -markerspan.outerWidth() / 2, -markerspan.outerHeight() / 2));
                markerspan.offset({'left': textPos.x - 4, 'top': textPos.y});
            }
        }

        var shapeSVG = shape.makeSvg(svg[0]);
        shapeSVG.setAttribute('class', 'dropzone ' + colourClass);
    };

    /**
     * Converts the relative x and y position coordinates into
     * absolute x and y position coordinates.
     *
     * @param {Point} point relative to the background image.
     * @returns {Point} point relative to the page.
     */
    DragDropMarkersQuestion.prototype.convertToWindowXY = function(point) {
        var bgImage = this.bgImage();
        // The +1 seems rather odd, but seems to give the best results in
        // the three main browsers at a range of zoom levels.
        // (Its due to the 1px border around the image, that shifts the
        // image pixels by 1 down and to the left.)
        return point.offset(bgImage.offset().left + 1, bgImage.offset().top + 1);
    };

    /**
     * Determine what drag items need to be shown and
     * return coords of all drag items except any that are currently being dragged
     * based on contents of hidden inputs and whether drags are 'infinite' or how many
     * drags should be shown.
     *
     * @param {jQuery} inputNode
     * @returns {Point[]} coordinates of however many copies of the drag item should be shown.
     */
    DragDropMarkersQuestion.prototype.getCoords = function(inputNode) {
        var coords = [],
            val = $(inputNode).val();
        if (val !== '') {
            var coordsStrings = val.split(';');
            for (var i = 0; i < coordsStrings.length; i++) {
                coords[i] = this.convertToWindowXY(Shapes.Point.parse(coordsStrings[i]));
            }
        }
        return coords;
    };

    /**
     * Singleton that tracks all the DragDropToTextQuestions on this page, and deals
     * with event dispatching.
     *
     * @type {Object}
     */
    var questionManager = {

        /**
         * {boolean} ensures that the event handlers are only initialised once per page.
         */
        eventHandlersInitialised: false,

        /**
         * {Object} all the questions on this page, indexed by containerId (id on the .que div).
         */
        questions: {}, // An object containing all the information about each question on the page.

        /**
         * Initialise one question.
         *
         * @param {String} containerId the id of the div.que that contains this question.
         * @param {boolean} readOnly whether the question is read-only.
         * @param {Object[]} visibleDropZones data on any drop zones to draw as part of the feedback.
         */
        // eslint-disable-next-line no-unused-vars
        init: function(containerId, readOnly, visibleDropZones) {
            questionManager.questions[containerId] = new DragDropMarkersQuestion(containerId, readOnly, visibleDropZones);
            if (!questionManager.eventHandlersInitialised) {
                questionManager.setupEventHandlers();
                questionManager.eventHandlersInitialised = true;
            }
            // eslint-disable-next-line no-console
            console.log('Done');
        },

        /**
         * Set up the event handlers that make this question type work. (Done once per page.)
         */
        setupEventHandlers: function() {
            $('body')
                .on('mousedown touchstart',
                    '.que.ddmarker:not(.qtype_ddmarker-readonly) div.draghomes .marker', questionManager.handleDragStart)
                .on('mousedown touchstart',
                    '.que.ddmarker:not(.qtype_ddmarker-readonly) div.droparea .marker', questionManager.handleDragStart);
        },

        /**
         * Handle mouse down / touch start events on markers.
         * @param {Event} e the DOM event.
         */
        handleDragStart: function(e) {
            e.preventDefault();
            var question = questionManager.getQuestionForEvent(e);
            if (question) {
                question.handleDragStart(e);
            }
        },

        /**
         * Given an event, work out which question it effects.
         * @param {Event} e the event.
         * @returns {DragDropMarkersQuestion|undefined} The question, or undefined.
         */
        getQuestionForEvent: function(e) {
            var containerId = $(e.currentTarget).closest('.que.ddmarker').attr('id');
            return questionManager.questions[containerId];
        }
    };

    /**
     * @alias module:qtype_ddmarker/question
     */
    return {
        /**
         * Initialise one drag-drop markers question.
         *
         * @param {String} containerId id of the outer div for this question.
         * @param {String} bgImgUrl the URL of the background image.
         * @param {boolean} readOnly whether the question is being displayed read-only.
         * @param {String[]} visibleDropZones the geometry of any drop-zones to show.
         */
        init: questionManager.init
    };

});
