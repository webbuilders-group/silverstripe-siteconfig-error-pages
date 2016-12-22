(function($) {
    $.entwine('ss', function($) {
        /**
         * Adds the history tab
         */
        $('.ErrorPage-edit.cms-edit-form .cms-content-header-tabs .ui-tabs-nav').entwine({
            onmatch: function() {
                this._super();
                
                this.append(
                            '<li class="ui-state-default ui-corner-top" role="tab" tabindex="-1" aria-controls="Root_History">'+
                                '<a href="'+this.closest('.cms-edit-form').attr('data-history-link')+'" class="cms-panel-link error-page-history-tab" role="presentation" tabindex="-1">History</a>'+
                            '</li>');
            }
        });
        
        /**
         * Handles when the user selects the history tab, primarily just ensures the correct cms menu item is selected
         */
        $('.ErrorPage-edit.cms-edit-form .cms-content-header-tabs .ui-tabs-nav .error-page-history-tab').entwine({
            onclick: function(e) {
                var result=this._super(e);
                
                $('.cms-menu-list li#Menu-CMSPagesController').select();
                
                return result;
            }
        });
        
        /**
         * Enable save buttons upon detecting changes to content.
         * "changed" class is added by jQuery.changetracker.
         */
        $('.ErrorPage-edit.cms-edit-form.changed').entwine({
            onmatch: function(e) {
                this.find('button[name=action_doSave]').button('option', 'showingAlternate', true);
                this.find('button[name=action_doPublish]').button('option', 'showingAlternate', true);
                
                this._super(e);
            },
            onunmatch: function(e) {
                var saveButton=this.find('button[name=action_save]');
                if(saveButton.data('button')) {
                    saveButton.button('option', 'showingAlternate', false);
                }
                
                var publishButton=this.find('button[name=action_publish]');
                if(publishButton.data('button')) {
                    publishButton.button('option', 'showingAlternate', false);
                }
                
                this._super(e);
            }
        });
        
        $('.ErrorPage-edit.cms-edit-form .Actions button[name=action_publish]').entwine({
            /**
             * Bind to ssui.button event to trigger stylistic changes.
             */
            onbuttonafterrefreshalternate: function() {
                if(this.button('option', 'showingAlternate')) {
                    this.addClass('ss-ui-action-constructive');
                }else {
                    this.removeClass('ss-ui-action-constructive');
                }
            }
        });
        
        $('.ErrorPage-edit.cms-edit-form .Actions button[name=action_save]').entwine({
            /**
             * Bind to ssui.button event to trigger stylistic changes.
             */
            onbuttonafterrefreshalternate: function() {
                if(this.button('option', 'showingAlternate')) {
                    this.addClass('ss-ui-action-constructive');
                }else {
                    this.removeClass('ss-ui-action-constructive');
                }
            }
        });
        
        $('.ErrorPage-edit.cms-edit-form #Form_ItemEditForm_action_archive').entwine({
            /**
             * Asks the user to confirm before archiving
             */
            onclick: function(e) {
                if(confirm('Are you sure you want to archive this error page?\n\nIf this error page is published it will be unpublished and sent to the archive.')) {
                    return this._super(e);
                }else {
                    return false;
                }
            }
        });
        
        /**
         * Enable save buttons upon detecting changes to content.
         * "changed" class is added by jQuery.changetracker.
         */
        $('.ErrorPage-edit.cms-edit-form.changed').entwine({
            onmatch: function(e) {
                this.find('button[name=action_save]').button('option', 'showingAlternate', true);
                this.find('button[name=action_publish]').button('option', 'showingAlternate', true);
                
                this._super(e);
            },
            onunmatch: function(e) {
                var saveButton=this.find('button[name=action_save]');
                if(saveButton.data('button')) {
                    saveButton.button('option', 'showingAlternate', false);
                }
                
                var publishButton=this.find('button[name=action_publish]');
                if(publishButton.data('button')) {
                    publishButton.button('option', 'showingAlternate', false);
                }
                
                this._super(e);
            }
        });
        
        $('.ErrorPage-edit.cms-edit-form .Actions button[name=action_publish]').entwine({
            /**
             * Bind to ssui.button event to trigger stylistic changes.
             */
            onbuttonafterrefreshalternate: function() {
                if (this.button('option', 'showingAlternate')) {
                    this.addClass('ss-ui-action-constructive');
                }else {
                    this.removeClass('ss-ui-action-constructive');
                }
            }
        });

        $('.ErrorPage-edit.cms-edit-form .Actions button[name=action_save]').entwine({
            /**
             * Bind to ssui.button event to trigger stylistic changes.
             */
            onbuttonafterrefreshalternate: function() {
                if (this.button('option', 'showingAlternate')) {
                    this.addClass('ss-ui-action-constructive');
                }else {
                    this.removeClass('ss-ui-action-constructive');
                }
            }
        });
    });
})(jQuery);