<?php
namespace WebbuildersGroup\SiteConfigErrorPages\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfigLeftAndMain;
use SilverStripe\View\HTML;

class ErrorPageExtension extends DataExtension
{
    public function MetaTags(&$tags)
    {
        $tags = str_replace(
            HTML::createTag(
                'meta',
                [
                    'name' => 'x-cms-edit-link',
                    'content' => $this->owner->obj('CMSEditLink')->forTemplate(),
                ]
            ),
            HTML::createTag(
                'meta',
                [
                    'name' => 'x-cms-edit-link',
                    'content' => Controller::join_links('admin', SiteConfigLeftAndMain::config()->url_segment, 'EditForm/field/ErrorPages/item', $this->owner->ID, '/edit'),
                ]
            ),
            $tags
        );
    }
}
