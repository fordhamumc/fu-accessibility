/**
 * Require Alt Tags
 * Adapted from: UF Health Require Image Alt Tags (https://github.com/UFHealth/ufhealth-require-image-alt-tags)
 */

(function() {
    'use strict';

    /**
     * Checks the media form for alt text
     *
     * @since   0.0.2
     * @param   {boolean}   showNotice  Whether to show the alert
     *
     * @returns {boolean}
     */
    var checkForAlt = function (showNotice) {
        var notice         = ('boolean' === typeof showNotice) ? showNotice : false,
            parent         = document.querySelector('.media-frame-toolbar .media-toolbar-primary'),
            selectedImages = document.querySelectorAll('.media-frame-content ul.attachments li[aria-checked="true"]'),
            canProceed     = true,
            badImages      = [];

        // Clear all the marked ones first.
        document.querySelectorAll('.fu-needs-alt-text').forEach(function (li) {
            li.classList.remove('fu-needs-alt-text');
        });

        if (0 === selectedImages.length) { // This is seen in some modals.
            var details = document.querySelector('.attachment-details'),
                imageId, altText;
            if (details) {
                imageId = details.getAttribute('data-id');
            }
            // Handle image uploads if there is a multi-select box (normal image insertion).
            if ('undefined' !== typeof imageId) {

                var image = wp.media.model.Attachment.get(imageId);
                altText = image.get('alt');

            } else { // Handle featured image, replace image, etc.

                // Different forms have different markup so attempt to address accordingly.
                var hasLabel = document.querySelector('.media-modal-content label[data-setting="alt"] input'),
                    noLabel  = document.querySelector('.media-frame-content input[data-setting="alt"]');

                if (hasLabel) {
                    altText = hasLabel.value;

                } else if (noLabel) {
                    altText = noLabel.value;
                }
            }

            // If we don't have an alt text field or don't even have a media form we're OK.
            if (!document.querySelector('.media-sidebar.visible') || ( altText.length && 0 < altText.length )) {
                parent.classList.add('fu-has-alt-text');
                return true;
            }

            // Remove the mask that allows the button to be pushed.
            parent.classList.remove('fu-has-alt-text');

            if (notice) {
                alert(AltTagsCopy.editTxst);
            }

            return false;

        } else { // We've selected one or more in a normal box.

            selectedImages.forEach(function (li) {

                var imageId = li.getAttribute('data-id'),
                    image   = wp.media.model.Attachment.get(imageId),
                    altText = image.get('alt');

                if ('undefined' !== typeof imageId) { // It's not actually an image or even an uploaded item.

                    if (altText.length || 'image' !== image.get('type')) { //looks like we're OK on this one.
                        parent.classList.add('fu-has-alt-text');
                        li.classList.remove('fu-needs-alt-text');

                    } else { // Mark it 0 dude.
                        li.classList.add('fu-needs-alt-text');
                        badImages.push(image.get('title'));
                        canProceed = false;
                    }
                }
            });

            if (false === canProceed) {

                parent.classList.remove('fu-has-alt-text');

                if (notice) {
                    var imageList = '\n\n';
                    for (var i = 0, l = badImages.length; i < l; i++) {
                        imageList = imageList + badImages[i] + '\n\n';
                    }
                    alert(AltTagsCopy.disclaimer + '\n\n' + AltTagsCopy.txt + ':' + imageList);
                }

                return false;
            }

            return true;
        }
    };


    /**
     * Event delegation
     *
     * @since   0.0.2
     * @param   {element|string}    target      The element to attach the event listener
     * @param   {string}            events      One or more space-separated event types
     * @param   {string}            selector    A selector string to filter the descendants of the selected elements that trigger the event.
     * @param   {function}          handler     A function to execute when the event is triggered.
     *
     */
    var on = function (target, events, selector, handler) {
        var element = (typeof target === 'string') ? document.querySelector(target) : target;

        events.split(" ").forEach(function(eventName) {
            element.addEventListener(eventName, function (event) {
                var possibleTargets = element.querySelectorAll(selector);
                var eventTarget = event.target;

                for (var i = 0; i < possibleTargets.length; i++) {
                    var el = eventTarget;
                    var p = possibleTargets[i];

                    while (el && el !== element) {
                        if (el === p) {
                            return handler.call(p, event);
                        }

                        el = el.parentNode;
                    }
                }
            });
        })

    };

    document.addEventListener("DOMContentLoaded", function() {
        on('body', 'keyup', '.media-modal-content label[data-setting="alt"] input, .media-frame-content input[data-setting="alt"]', checkForAlt);
        on('body', 'mouseover click', '.media-frame-toolbar .media-toolbar-primary', function (e) {
            checkForAlt(e.type === 'click');
        });
    });

})();