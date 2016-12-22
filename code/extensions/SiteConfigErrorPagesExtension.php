<?php
class SiteConfigErrorPagesExtension extends DataExtension {
    /**
     * Updates the CMS fields adding the fields defined in this extension
     * @param FieldList $fields Field List that new fields will be added to
     */
    public function updateCMSFields(FieldList $fields) {
        //Reset Versioned
        Versioned::reset();
        
        
        $fields->findOrMakeTab('Root.ErrorPages', _t('SiteConfigErrorPagesExtension.ERROR_PAGES', 'Error Pages'));
        $fields->addFieldToTab('Root.ErrorPages', $gridField=new GridField('ErrorPages', _t('SiteConfigErrorPagesExtension.ERROR_PAGES', 'Error Pages'), ErrorPage::get(), GridFieldConfig_RecordEditor::create(10)));
        $gridField->getConfig()
                            ->removeComponentsByType('GridFieldDeleteAction')
                            ->removeComponentsByType('GridFieldDataColumns')
                            ->removeComponentsByType('GridFieldEditButton')
                            ->addComponent(new GridFieldDeletedManipulator(), 'GridFieldToolbarHeader')
                            ->addComponent(new GridFieldDeletedColumns())
                            ->addComponent(new GridFieldDeletedEditButton())
                            ->addComponent(new GridFieldDeletedRestoreButton())
                            ->addComponent(new GridFieldDeletedToggle('buttons-before-left'))
                            ->getComponentByType('GridFieldDataColumns')
                                ->setDisplayFields(array(
                                                        'Title'=>_t('SiteConfigErrorPagesExtension.PAGE_NAME', 'Page name'),
                                                        'ErrorCode'=>_t('SiteConfigErrorPagesExtension.ERROR_CODE', 'Error Code'),
                                                        'isPublished'=>_t('SiteConfigErrorPagesExtension.PUBLISHED', 'Published')
                                                    ))
                                ->setFieldCasting(array(
                                                        'isPublished'=>'Boolean->Nice'
                                                    ));
        
        $gridField->getConfig()
                            ->getComponentByType('GridFieldDetailForm')
                                ->setItemRequestClass('ErrorPageItemRequestHandler');
    }
}
?>