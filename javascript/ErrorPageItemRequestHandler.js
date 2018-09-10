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
        
        $('.ErrorPage-edit.cms-edit-form .Actions button[name=action_publish]').entwine({
            /**
             * Bind to ssui.button event to trigger stylistic changes.
             */
            onbuttonafterrefreshalternate: function() {
                if(this.data('showingAlternate')) {
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
                if(this.data('showingAlternate')) {
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
                // Update all buttons with alternate text
                this.find('button[data-text-alternate]').each(function() {
                    var button = $(this);
                    var buttonTitle = button.find('.btn__title');
                    
                    // Set alternate-text
                    var alternateText = button.data('textAlternate');
                    if (alternateText) {
                        button.data('textStandard', buttonTitle.text());
                        buttonTitle.text(alternateText);
                    }
                    
                    // Extra classes can be declared explicitly (legacy)
                    var alternateClasses = button.data('btnAlternate');
                    if (alternateClasses) {
                        button.data('btnStandard', button.attr('class'));
                        button.attr('class', alternateClasses);
                        button
                            .removeClass('btn-outline-secondary')
                            .addClass('btn-primary');
                    }
                    
                    // Extra classes can also be specified as add / remove
                    var alternateClassesAdd = button.data('btnAlternateAdd');
                    if (alternateClassesAdd) {
                        button.addClass(alternateClassesAdd);
                    }
                    
                    var alternateClassesRemove = button.data('btnAlternateRemove');
                    if (alternateClassesRemove) {
                        button.removeClass(alternateClassesRemove);
                    }
                });

                this._super(e);
            },
            onunmatch: function(e) {
                this.find('button[data-text-alternate]').each(function() {
                    var button = $(this);
                    var buttonTitle = button.find('.btn__title');

                    // Revert extra classes
                    var standardText = button.data('textStandard');
                    if (standardText) {
                        buttonTitle.text(standardText);
                    }

                    // Extra classes can be declared explicitly (legacy)
                    var standardClasses = button.data('btnStandard');
                    if (standardClasses) {
                        button.attr('class', standardClasses);
                        button
                            .addClass('btn-outline-secondary')
                            .removeClass('btn-primary');
                    }

                    // Extra classes can also be specified as add / remove
                    // Note: Reverse of onMatch
                    var alternateClassesAdd = button.data('btnAlternateAdd');
                    if (alternateClassesAdd) {
                        button.removeClass(alternateClassesAdd);
                    }
                    var alternateClassesRemove = button.data('btnAlternateRemove');
                    if (alternateClassesRemove) {
                        button.addClass(alternateClassesRemove);
                    }
                });

                this._super(e);
            }
        });
        
        $('.ErrorPage-edit.cms-edit-form .Actions button[name=action_publish]').entwine({
            /**
             * Bind to ssui.button event to trigger stylistic changes.
             */
            onbuttonafterrefreshalternate: function() {
                if (this.data('showingAlternate')) {
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
                if (this.data('showingAlternate')) {
                    this.addClass('ss-ui-action-constructive');
                }else {
                    this.removeClass('ss-ui-action-constructive');
                }
            }
        });
        
        
        /**
         * Page Type Visibilty toggle
         */
        $('.ErrorPage-edit.cms-edit-form #Form_ItemEditForm_ParentType input[name=ParentType]').entwine({
            onmatch: function() {
                $('.ErrorPage-edit.cms-edit-form div.field.parent-id-field').hide();
            },
            
            onchange: function(e) {
                var parentPageField=$('.ErrorPage-edit.cms-edit-form div.field.parent-id-field');
                if(this.val()=='subpage') {
                    parentPageField.show();
                }else {
                    parentPageField.hide();
                }
            }
        });
        
        
        /**
         * Viewer Groups Visibilty toggle
         */
        $('.ErrorPage-edit.cms-edit-form #Form_ItemEditForm_CanViewType input[name=CanViewType]').entwine({
            onmatch: function() {
                $('.ErrorPage-edit.cms-edit-form div.field.viewer-groups-field').hide();
            },
            
            onchange: function(e) {
                var viewerGroupsField=$('.ErrorPage-edit.cms-edit-form div.field.viewer-groups-field');
                if(this.val()=='OnlyTheseUsers') {
                    viewerGroupsField.show();
                }else {
                    viewerGroupsField.hide();
                }
            }
        });
        
        
        /**
         * Editor Groups Visibilty toggle
         */
        $('.ErrorPage-edit.cms-edit-form #Form_ItemEditForm_CanEditType input[name=CanEditType]').entwine({
            onmatch: function() {
                $('.ErrorPage-edit.cms-edit-form div.field.editor-groups-field').hide();
            },
            
            onchange: function(e) {
                var editorGroupsField=$('.ErrorPage-edit.cms-edit-form div.field.editor-groups-field');
                if(this.val()=='OnlyTheseUsers') {
                    editorGroupsField.show();
                }else {
                    editorGroupsField.hide();
                }
            }
        });
    });
})(jQuery);