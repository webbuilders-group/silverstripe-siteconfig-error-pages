<?php
namespace WebbuildersGroup\SiteConfigErrorPages\Extensions;

use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\Versioned;
use WebbuildersGroup\GridFieldDeletedItems\Forms\GridFieldDeletedColumns;
use WebbuildersGroup\GridFieldDeletedItems\Forms\GridFieldDeletedEditButton;
use WebbuildersGroup\GridFieldDeletedItems\Forms\GridFieldDeletedManipulator;
use WebbuildersGroup\GridFieldDeletedItems\Forms\GridFieldDeletedRestoreButton;
use WebbuildersGroup\GridFieldDeletedItems\Forms\GridFieldDeletedToggle;
use WebbuildersGroup\SiteConfigErrorPages\Forms\GridField\ErrorPageItemRequestHandler;


class SiteConfigExtension extends DataExtension {
    /**
     * Updates the CMS fields adding the fields defined in this extension
     * @param FieldList $fields Field List that new fields will be added to
     */
    public function updateCMSFields(FieldList $fields) {
        //Reset Versioned
        Versioned::set_reading_mode('Stage.'.Versioned::DRAFT);
        
        
        $fields->findOrMakeTab('Root.ErrorPages', _t('WebbuildersGroup\\SiteConfigErrorPages\\Extensions\\SiteConfigErrorPagesExtension.ERROR_PAGES', 'Error Pages'));
        $fields->addFieldToTab('Root.ErrorPages', $gridField=new GridField('ErrorPages', _t('WebbuildersGroup\\SiteConfigErrorPages\\Extensions\\SiteConfigErrorPagesExtension.ERROR_PAGES', 'Error Pages'), ErrorPage::get(), GridFieldConfig_RecordEditor::create(10)));
        $gridField->getConfig()
                            ->removeComponentsByType(GridFieldDeleteAction::class)
                            ->removeComponentsByType(GridFieldDataColumns::class)
                            ->removeComponentsByType(GridFieldEditButton::class)
                            ->addComponent(new GridFieldDeletedManipulator(), GridFieldToolbarHeader::class)
                            ->addComponent(new GridFieldDeletedColumns(), GridField_ActionMenu::class)
                            ->addComponent(new GridFieldDeletedEditButton(), GridField_ActionMenu::class)
                            ->addComponent(new GridFieldDeletedRestoreButton(), GridField_ActionMenu::class)
                            ->addComponent(new GridFieldDeletedToggle('buttons-before-left'))
                            ->getComponentByType(GridFieldDataColumns::class)
                                ->setDisplayFields(array(
                                                        'Title'=>_t('WebbuildersGroup\\SiteConfigErrorPages\\Extensions\\SiteConfigErrorPagesExtension.PAGE_NAME', 'Page name'),
                                                        'ErrorCode'=>_t('WebbuildersGroup\\SiteConfigErrorPages\\Extensions\\SiteConfigErrorPagesExtension.ERROR_CODE', 'Error Code'),
                                                        'isPublished'=>_t('WebbuildersGroup\\SiteConfigErrorPages\\Extensions\\SiteConfigErrorPagesExtension.PUBLISHED', 'Published'),
                                                        'IsModifiedOnStage'=>_t('WebbuildersGroup\\SiteConfigErrorPages\\Extensions\\SiteConfigErrorPagesExtension.MODIFIED', 'Modified')
                                                    ))
                                ->setFieldCasting(array(
                                                        'isPublished'=>'Boolean->Nice',
                                                        'IsModifiedOnStage'=>'Boolean->Nice'
                                                    ));
        
        $gridField->getConfig()
                            ->getComponentByType(GridFieldDetailForm::class)
                                ->setItemRequestClass(ErrorPageItemRequestHandler::class);
    }
}
?>