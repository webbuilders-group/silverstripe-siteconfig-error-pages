<?php
class ErrorPageItemRequestHandler extends GridFieldDetailForm_ItemRequest {
    private static $allowed_actions=array(
                                        'ItemEditForm',
                                        'edit',
                                        'view'
                                    );
    
    /**
     * Class to use when creating an error page
     * @config ErrorPageItemRequestHandler.error_page_class
     * @var string
     */
    private static $error_page_class='ErrorPage';
    
    
    /**
     * Gets the form used for editing the error page
     * @return Form
     */
    public function ItemEditForm() {
        $form=parent::ItemEditForm();
        
        $editFields=$form->Fields()->fieldByName('Root')->Tabs();
        $form->setFields(new FieldList(
                                        TabSet::create('Root',
                                                    new TabSet('Content', _t('ErrorPageItemRequestHandler.CONTENT', 'Content')),
                                                    new TabSet('Settings', _t('ErrorPageItemRequestHandler.SETTINGS', 'Settings'))
                                                )->setTemplate('CMSTabSet')
                                    ));
        
        $form->Fields()->addFieldsToTab('Root.Content', $editFields);
        $form->Fields()->addFieldsToTab('Root.Settings', $this->record->getSettingsFields()->setForm($form)->fieldByName('Root')->Tabs());
        
        
        //Reload the data since we added the settings fields
        $form->loadDataFrom($this->record, ($this->record->ID==0 ? Form::MERGE_IGNORE_FALSEISH:Form::MERGE_DEFAULT));
        
        
        $form->Fields()->dataFieldByName('ParentID')->addExtraClass('parent-id-field');
        $form->Fields()->dataFieldByName('ViewerGroups')->addExtraClass('viewer-groups-field');
        $form->Fields()->dataFieldByName('EditorGroups')->addExtraClass('editor-groups-field');
        
        
        $form->setActions($this->record->getCMSActions()->setForm($form));
        $actionsFlattened=$form->Actions()->dataFields();
        if($actionsFlattened) {
            foreach($actionsFlattened as $action) {
                $action->setUseButtonTag(true);
            }
        }
        
        $form->disableDefaultAction();
        
        $form->addExtraClass('ErrorPage-edit');
        $form->setAttribute('data-history-link', Controller::join_links(LeftAndMain::config()->url_base, CMSPageHistoryController::config()->url_segment, 'show', $this->record->ID));
        
        
        //Add the navigator if it doesn't exist
        if(!$form->Fields()->fieldByName('SilverStripeNavigator')) {
            $navField=LiteralField::create('SilverStripeNavigator', $this->getSilverStripeNavigator())->setForm($form)->setAllowHTML(true);
            $form->Fields()->push($navField);
             
            $form->addExtraClass('cms-previewable');
            $form->setTemplate('ErrorPageItemEditForm');
        }
        
        
        Requirements::javascript(SITECONFIG_ERROR_PAGES_DIR.'/javascript/ErrorPageItemRequestHandler.js');
        
        return $form;
    }
    
    /**
     * Handles request to edit the error page, if we're creating a new error page it is saved then redirected to editing that error page
     * @param SS_HTTPRequest $request HTTP Request Object
     * @return SS_HTTPResponse
     */
    public function edit($request) {
        if(!$this->record->exists()) {
            $addController=CMSPageAddController::create();
            $this->record=$addController->getNewItem('new-'.$this->config()->error_page_class, false);
            
            $form=$this->ItemEditForm();
            $this->extend('updateDoAdd', $this->record, $form);
            
            $this->record->Sort=DB::query('SELECT MAX("Sort") FROM "SiteTree" WHERE "ParentID" = 0')->value() + 1;
            
            try {
                $this->record->write();
            }catch(ValidationException $ex) {
                $form->sessionMessage($ex->getResult()->message(), 'bad');
        
                return $this->getToplevelController()->getResponseNegotiator()->respond($this->getRequest());
            }
        
            return $this->getToplevelController()->redirect(Controller::join_links($this->Link('edit'), (class_exists('Translatable') ? '?Locale='.$this->record->Locale:'')));
        }
        
        
        //If translatable exists and the current locale does not match the record locale redirect
        if(class_exists('Translatable') && Translatable::get_current_locale()!=$this->record->Locale) {
            return $this->getToplevelController()->redirect(Controller::join_links($this->Link('edit'), '?Locale='.$this->record->Locale));
        }
        
        
        return parent::edit($request);
    }
    
    /**
     * Handles request to view the error page
     * @param SS_HTTPRequest $request HTTP Request Object
     * @return SS_HTTPResponse
     */
    public function view($request) {
        //If translatable exists and the current locale does not match the record locale redirect
        if(class_exists('Translatable') && Translatable::get_current_locale()!=$this->record->Locale) {
            return $this->getToplevelController()->redirect(Controller::join_links($this->Link('edit'), '?Locale='.$this->record->Locale));
        }
        
        
        return parent::view($request);
    }
    
    /**
     * Handles saving the submission
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     * @see self::doSave()
     */
    public function save($data, Form $form) {
        return $this->doSave($data, $form);
    }
    
    /**
     * Handles saving the submission
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     */
    public function doSave($data, $form) {
        $record=$this->record;
        $controller=$this->getToplevelController();
        
        $record->HasBrokenLink=0;
        $record->HasBrokenFile=0;
        
        if(!$record->ObsoleteClassName) {
            $record->writeWithoutVersion();
        }
        
        // Update the class instance if necessary
        if(isset($data['ClassName']) && $data['ClassName']!=$record->ClassName) {
            $newClassName=$record->ClassName;
            // The records originally saved attribute was overwritten by $form->saveInto($record) before.
            // This is necessary for newClassInstance() to work as expected, and trigger change detection
            // on the ClassName attribute
            $record->setClassName($data['ClassName']);
            // Replace $record with a new instance
            $record=$record->newClassInstance($newClassName);
        }
        
        // save form data into record
        $form->saveInto($record);
        $record->write();
        
        // If the 'Save & Publish' button was clicked, also publish the page
        if(isset($data['publish']) && $data['publish']==1) {
            $record->doPublish();
        }
        
        
        if($this->gridField->getList()->byId($this->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        }else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL=$controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }
    
    /**
     * Processes reverting staging to match the live site
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     */
    public function rollback($data, Form $form) {
        $recordID=$this->record->ID;
        $record=Versioned::get_one_by_stage('SiteTree', 'Live', array('"SiteTree_Live"."ID"'=>$recordID));
        $controller=$this->getToplevelController();
        
        
        // a user can restore a page without publication rights, as it just adds a new draft state
        // (this action should just be available when page has been "deleted from draft")
        if($record && !$record->canEdit()) return Security::permissionFailure($this);
        if(!$record || !$record->ID) throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);
        
        $record->doRollbackTo('Live');
        
        Versioned::reset();
        
        $this->record=$this->gridField->getList()->byId($recordID);
        
        $form->sessionMessage(_t('CMSMain.ROLLEDBACKPUBv2', 'Rolled back to published version.'), 'good');
        if($this->record) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        }else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL=$controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }
    
    /**
     * Deletes this page from both live and stage
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     */
    public function archive($data, Form $form) {
        $record=$this->record;
        if(!$record || !$record->exists()) {
            throw new SS_HTTPResponse_Exception("Bad record ID #".$data['id'], 404);
        }
        
        if(!$record->canArchive()) {
            return Security::permissionFailure();
        }
        
        // Archive record
        $record->doArchive();
        
        $message=sprintf(_t('CMSMain.ARCHIVEDPAGE',"Archived page '%s'"), $record->Title);
        
        $toplevelController=$this->getToplevelController();
        if($toplevelController && $toplevelController instanceof LeftAndMain) {
            $backForm=$toplevelController->getEditForm();
            $backForm->sessionMessage($message, 'good', false);
        }else {
            $form->sessionMessage($message, 'good', false);
        }
        
        //when an item is deleted, redirect to the parent controller
        $controller = $this->getToplevelController();
        $controller->getRequest()->addHeader('X-Pjax', 'Content'); // Force a content refresh
        
        return $controller->redirect($this->getBacklink(), 302); //redirect back to admin section
    }
    
    /**
     * Handles publishing the error page
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     * @see self::doSave()
     */
    public function publish($data, Form $form) {
        $data['publish']='1';
        
        return $this->doSave($data, $form);
    }
    
    /**
     * Handles unpublishing the error page
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     */
    public function unpublish($data, Form $form) {
        $record=$this->record;
        $controller=$this->getToplevelController();
        
        if($record && !$record->canDeleteFromLive()) {
            return Security::permissionFailure($this);
        }
        
        if(!$record || !$record->ID) {
            throw new SS_HTTPResponse_Exception("Bad record ID #" . (int)$data['ID'], 404);
        }
        
        $record->doUnpublish();
        
        $form->sessionMessage(_t('CMSMain.REMOVEDPAGE', "Removed '{title}' from the published site", array('title'=>$record->Title)), 'good');
        if($this->gridField->getList()->byId($this->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        }else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL=$controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }
    
    /**
     * Wrapper to redirect back
     */
    public function redirectBack() {
        return $this->getToplevelController()->redirectBack();
    }
    
    /**
     * Used for preview controls, mainly links which switch between different states of the page.
     * @return ArrayData
     */
    protected function getSilverStripeNavigator($segment=null) {
        if($this->record) {
            $navigator=new SilverStripeNavigator($this->record);
            return $navigator->renderWith('LeftAndMain_SilverStripeNavigator');
        }else {
            return false;
        }
    }
     
    /**
     * Gets the preview link
     * @return string Link to view the record
     */
    public function LinkPreview() {
        return $this->record->Link();
    }
}
?>