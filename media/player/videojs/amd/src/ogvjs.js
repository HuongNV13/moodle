/**
 * ogv.js Tech plugin for Video.JS.
 *
 * @module     media_videojs/ogvjs
 * @copyright  2021 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import videojs from 'media_videojs/video-lazy';
const Tech = videojs.getComponent('Tech');

// Begin Moodle hack.
const OGVCompat = window.OGVCompat;
const OGVLoader = window.OGVLoader;
const OGVPlayer = window.OGVPlayer;
// End Moodle hack.

const defineLazyProperty = function(obj, key, getValue, setter = true) {
    const set = (value) =>
        Object.defineProperty(obj, key, {value, enumerable: true, writable: true});

    const options = {
        configurable: true,
        enumerable: true,
        get() {
            const value = getValue();

            set(value);

            return value;
        }
    };

    if (setter) {
        options.set = set;
    }

    return Object.defineProperty(obj, key, options);
};

class OgvJS extends Tech {
    constructor(options, ready) {
        super(options, ready);

        this.el_.src = options.source.src;
        OgvJS.setIfAvailable(this.el_, 'autoplay', options.autoplay);
        OgvJS.setIfAvailable(this.el_, 'loop', options.loop);
        OgvJS.setIfAvailable(this.el_, 'poster', options.poster);
        OgvJS.setIfAvailable(this.el_, 'preload', options.preload);

        this.on('loadedmetadata', () => {
            window.console.log('Loaded');
            window.console.log(this.el_.ogvjsAudioChannels);
            window.console.log(this.el_.ogvjsAudioSampleRate);
            this.triggerReady();
        });
    }

    dispose() {
        this.el_.removeEventListener('framecallback', this.onFrameUpdate);
        super.dispose();
    }

    createEl() {
        let options = this.options_;

        if (options.base) {
            OGVLoader.base = options.base;
        } else {
            throw new Error('Please specify the base for the ogv.js library');
        }

        let el = new OGVPlayer(options);
        el.className += ' vjs-tech';
        options.tag = el;

        return el;
    }

    play() {
        this.el_.play();
    }

    pause() {
        this.el_.pause();
    }

    paused() {
        return this.el_.paused;
    }

    currentTime() {
        return this.el_.currentTime;
    }

    setCurrentTime(seconds) {
        try {
            this.el_.currentTime = seconds;
        } catch (e) {
            videojs.log(e, 'Video is not ready. (Video.js)');
        }
    }

    duration() {
        if (this.el_.duration && this.el_.duration !== Infinity) {
            return this.el_.duration;
        }

        return 0;
    }

    buffered() {
        return this.el_.buffered;
    }

    volume() {
        return this.el_.hasOwnProperty('volume') ? this.el_.volume : 1;
    }

    setVolume(percentAsDecimal) {
        if (this.el_.hasOwnProperty('volume')) {
            this.el_.volume = percentAsDecimal;
        }
    }

    muted() {
        return this.el_.muted;
    }

    setMuted(muted) {
        this.el_.muted = !!muted;
    }

    width() {
        return this.el_.offsetWidth;
    }

    height() {
        return this.el_.offsetHeight;
    }

    src(src) {
        if (typeof src === 'undefined') {
            return this.el_.src;
        }
        this.setSrc(src);
    }

    setSrc(src) {
        this.el_.src = src;
    }

    load() {
        this.el_.load();
    }

    currentSrc() {
        if (this.currentSource_) {
            return this.currentSource_.src;
        }
        return this.el_.currentSrc;
    }

    poster() {
        return this.el_.poster;
    }

    setPoster(val) {
        this.el_.poster = val;
    }

    preload() {
        return this.el_.preload || 'none';
    }

    setPreload(val) {
        if (this.el_.hasOwnProperty('preload')) {
            this.el_.preload = val;
        }
    }

    autoplay() {
        return this.el_.autoplay || false;
    }

    setAutoplay(val) {
        if (this.el_.hasOwnProperty('autoplay')) {
            this.el_.autoplay = !!val;
            return;
        }
    }

    controls() {
        return this.el_controls || false;
    }

    setControls(val) {
        if (this.el_.hasOwnProperty('controls')) {
            this.el_.controls = !!val;
        }
    }

    loop() {
        return this.el_.loop || false;
    }

    setLoop(val) {
        if (this.el_.hasOwnProperty('loop')) {
            this.el_.loop = !!val;
        }
    }

    error() {
        return this.el_.error;
    }

    seeking() {
        return this.el_.seeking;
    }

    seekable() {
        return this.el_.seekable;
    }

    ended() {
        return this.el_.ended;
    }

    defaultMuted() {
        return this.el_.defaultMuted || false;
    }

    playbackRate() {
        return this.el_.playbackRate || 1;
    }

    played() {
        return this.el_.played;
    }

    setPlaybackRate(val) {
        if (this.el_.hasOwnProperty('playbackRate')) {
            this.el_.playbackRate = val;
        }
    }

    networkState() {
        return this.el_.networkState;
    }

    readyState() {
        return this.el_.readyState;
    }

    videoWidth() {
        return this.el_.videoWidth;
    }

    videoHeight() {
        return this.el_.videoHeight;
    }

    supportsFullScreen() {
        return false;
    }
}

OgvJS.setIfAvailable = function(el, name, value) {
    if (el.hasOwnProperty(name)) {
        el[name] = value;
    }
};

OgvJS.isSupported = function() {
    return OGVCompat.supported('OGVPlayer');
};

OgvJS.canPlayType = function(type) {
    return (type.indexOf('/ogg') !== -1 || type.indexOf('/webm')) ? 'maybe' : '';
};

OgvJS.canPlaySource = function(srcObj) {
    return OgvJS.canPlayType(srcObj.type);
};

OgvJS.canControlVolume = function() {
    let p = new OGVPlayer();

    return p.hasOwnProperty('volume');
};

OgvJS.canMuteVolume = function() {
    return true;
};

OgvJS.canControlPlaybackRate = function() {
    return true;
};

OgvJS.supportsNativeTextTracks = function() {
    return false;
};

OgvJS.supportsFullscreenResize = function() {
    return true;
};

OgvJS.supportsProgressEvents = function() {
    return true;
};

OgvJS.supportsTimeupdateEvents = function() {
    return true;
};

OgvJS.Events = [
    'loadstart',
    'suspend',
    'abort',
    'error',
    'emptied',
    'stalled',
    'loadedmetadata',
    'loadeddata',
    'canplay',
    'canplaythrough',
    'playing',
    'waiting',
    'seeking',
    'seeked',
    'ended',
    'durationchange',
    'timeupdate',
    'progress',
    'play',
    'pause',
    'ratechange',
    'resize',
    'volumechange'
];

[
    ['featuresVolumeControl', 'canControlVolume'],
    ['featuresMuteControl', 'canMuteVolume'],
    ['featuresPlaybackRate', 'canControlPlaybackRate'],
    ['featuresNativeTextTracks', 'supportsNativeTextTracks'],
    ['featuresFullscreenResize', 'supportsFullscreenResize'],
    ['featuresProgressEvents', 'supportsProgressEvents'],
    ['featuresTimeupdateEvents', 'supportsTimeupdateEvents'],
].forEach(function([key, fn]) {
    defineLazyProperty(OgvJS.prototype, key, () => OgvJS[fn](), true);
});

Tech.registerTech('OgvJS', OgvJS);
export default OgvJS;
