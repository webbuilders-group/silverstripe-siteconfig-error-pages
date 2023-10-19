<?php
namespace WebbuildersGroup\SiteConfigErrorPages\Extensions;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfigLeftAndMain;

class CMSMainExtension extends Extension
{
    /**
     * Redirects the error pages to settings
     */
    public function onAfterInit()
    {
        if ($this->owner->currentPage() instanceof ErrorPage && !$this->owner->redirectedTo()) {
            $this->owner->redirect(Controller::join_links('admin', SiteConfigLeftAndMain::config()->url_segment, 'EditForm/field/ErrorPages/item', $this->owner->currentPage()->ID, '/edit'));
        }
    }
    
    /**
     * Removes the error page from the available page options
     * @param FieldList $fields Fields used in the Add Page form
     */
    public function updatePageOptions(FieldList $fields)
    {
        $optionsField = $fields->dataFieldByName('PageType');
        if ($optionsField) {
            $optionsField->setSource(array_diff_key($optionsField->getSource(), ClassInfo::subclassesFor(ErrorPage::class)));
        }
    }
}
