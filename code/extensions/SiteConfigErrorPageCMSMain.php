<?php
class SiteConfigErrorPageCMSMain extends Extension {
    /**
     * Redirects the error pages to settings
     */
    public function onAfterInit() {
        if($this->owner->currentPage() instanceof ErrorPage) {
            $this->owner->redirect(Controller::join_links(LeftAndMain::config()->url_base, SiteConfigLeftAndMain::config()->url_segment, 'EditForm/field/ErrorPages/item', $this->owner->currentPage()->ID, '/edit'));
        }
    }
    
    /**
     * Removes the error page from the available page options
     * @param FieldList $fields Fields used in the Add Page form
     */
    public function updatePageOptions(FieldList $fields) {
        $optionsField=$fields->dataFieldByName('PageType');
        if($optionsField) {
            $optionsField->setSource(array_diff_key($optionsField->getSource(), ClassInfo::subclassesFor('ErrorPage')));
        }
    }
}
?>