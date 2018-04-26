(function() {
    'use strict';

    /**
     * Create a new MediaLibraryAltFilter we later will instantiate
     */
    var MediaLibraryAltFilter = wp.media.View.extend({

        tagName:    'input',
        className:  'attachment-filters',
        id:         'media-attachment-alt-filter',

        attributes: {
            type:   'checkbox'
        },

        events: {
            click: 'click'
        },

        /**
         * @returns {wp.media.view.MediaLibraryAltFilter} Returns itself to allow chaining
         */
        render: function() {
            this.el.value = 'no_alt';
            this.el.checked = this.model.escape('click');
            return this;
        },

        click: function ( event ) {
            if ( event.target.checked ) {
                this.model.set( event.target.value, true );
            } else {
                this.model.unset( event.target.value );
            }
        }
    });
    var MediaLibraryAuthorFilter = wp.media.view.AttachmentFilters.extend({
        id: 'media-attachment-author-filter',
        createFilters: function() {
            var filters = {};
            _.each( MediaLibraryAuthorFilterData.authors || {}, function( value, index ) {
                filters[ index ] = {
                    text: value.display_name,
                    props: {
                        author: value.ID,
                    }
                };
            });
            filters.all = {
                text:  MediaLibraryAdditionalFilterLabels.authorsAll,
                props: {
                    author: ''
                },
                priority: 10
            };
            this.filters = filters;
        }
    });
    /**
     * Extend and override wp.media.view.AttachmentsBrowser to include our new filter
     */
    var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
    wp.media.view.AttachmentsBrowser = AttachmentsBrowser.extend({
        createToolbar: function() {
            // Make sure to load the original toolbar
            AttachmentsBrowser.prototype.createToolbar.call( this );
            this.toolbar.set( 'MediaLibraryAltFilter', new MediaLibraryAltFilter({
                controller: this.controller,
                model:      this.collection.props,
                priority: -74
            }).render() );
            this.toolbar.set( 'altFilterLabel', new wp.media.view.Label({
                className: 'attachment-filters-label',
                value: MediaLibraryAdditionalFilterLabels.alt,
                attributes: {
                    'for': 'media-attachment-alt-filter'
                },
                priority: -73
            }).render() );
            this.toolbar.set( 'MediaLibraryAuthorFilter', new MediaLibraryAuthorFilter({
                controller: this.controller,
                model:      this.collection.props,
                priority: -75
            }).render() );
            this.toolbar.set( 'authorFilterLabel', new wp.media.view.Label({
                value: MediaLibraryAdditionalFilterLabels.authors,
                attributes: {
                    'for': 'media-attachment-author-filter'
                },
                priority: -76
            }).render() );

        }
    });

})();