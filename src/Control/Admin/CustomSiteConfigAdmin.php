<?php
namespace WebbuildersGroup\SiteConfigErrorPages\Control\Admin;

use SilverStripe\Control\Controller;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\SiteConfig\SiteConfigLeftAndMain;

class CustomSiteConfigAdmin extends SiteConfigLeftAndMain
{
    /**
     * Render $PreviewPanel content
     * @return DBHTMLText
     */
    public function PreviewPanel()
    {
        $template = singleton(CMSMain::class)->getTemplatesWithSuffix('_PreviewPanel');
        
        // Only render sections with preview panel
        if ($template) {
            return $this->renderWith($template);
        }
        
        return null;
    }
}
