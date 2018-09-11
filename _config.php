<?php
use SilverStripe\Admin\CMSMenu;
use SilverStripe\SiteConfig\SiteConfigLeftAndMain;

CMSMenu::remove_menu_class(SiteConfigLeftAndMain::class);
