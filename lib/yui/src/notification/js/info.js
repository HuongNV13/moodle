/* global DIALOGUE_PREFIX */

/**
 * A dialogue type designed to display informative messages to users.
 *
 * @module moodle-core-notification
 */

var RETURN_FOCUS = 'returnFocus';

/**
 * Extends core Dialogue to provide a type of dialogue which can be used
 * for informative message which are modal, and centered.
 *
 * @param {Object} config Object literal specifying the dialogue configuration properties.
 * @constructor
 * @class M.core.notification.info
 * @extends M.core.dialogue
 */
var INFO = function() {
    INFO.superclass.constructor.apply(this, arguments);
};

Y.extend(INFO, M.core.dialogue, {
    initializer: function() {
        this.show();
    },

    destroy: function() {
        var focusNode = this.get(RETURN_FOCUS);
        if (focusNode) {
            // Make sure focus is set after all.
            setTimeout(function() {
                focusNode.focus();
            });
        }

        return INFO.superclass.destroy.apply(this);
    },

    hide: function(e) {
        if (e) {
            // If the event was closed by an escape key event, then we need to check that this
            // dialogue is currently focused to prevent closing all dialogues in the stack.
            if (e.type === 'key' && e.keyCode === 27 && !this.get('focused')) {
                return;
            }
        }
        var focusNode = this.get(RETURN_FOCUS);
        if (focusNode) {
            // Make sure focus is set after all.
            setTimeout(function() {
                focusNode.focus();
            });
        }

        return INFO.superclass.hide.call(this, arguments);
    }
}, {
    NAME: 'Moodle information dialogue',
    CSS_PREFIX: DIALOGUE_PREFIX
});

Y.Base.modifyAttrs(INFO, {
   /**
    * Whether the widget should be modal or not.
    *
    * We override this to change the default from false to true for a subset of dialogues.
    *
    * @attribute modal
    * @type Boolean
    * @default true
    */
    modal: {
        validator: Y.Lang.isBoolean,
        value: true
    },

    /**
     * The DOM node which will be focused after close the widget.
     * @attribute returnFocus
     * @type DOM element
     * @default null
     */
    returnFocus: {
        value: null
    }
});

M.core.notification = M.core.notification || {};
M.core.notification.info = INFO;
