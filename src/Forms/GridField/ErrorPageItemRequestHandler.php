<?php
namespace WebbuildersGroup\SiteConfigErrorPages\Forms\GridField;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\Navigator\SilverStripeNavigator;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SilverStripe\VersionedAdmin\Controllers\CMSPageHistoryViewerController;
use SilverStripe\View\Requirements;

class ErrorPageItemRequestHandler extends GridFieldDetailForm_ItemRequest
{
    private static $allowed_actions = [
        'ItemEditForm',
        'edit',
        'view'
    ];

    /**
     * Class to use when creating an error page
     * @config ErrorPageItemRequestHandler.error_page_class
     * @var string
     */
    private static $error_page_class = ErrorPage::class;


    /**
     * Gets the form used for editing the error page
     * @return Form
     */
    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();

        if ($form == null) {
            return;
        }

        $editFields = $form->Fields()->fieldByName('Root')->Tabs();
        $form->setFields(new FieldList(
            TabSet::create(
                'Root',
                new TabSet('Content', _t(ErrorPageItemRequestHandler::class . '.CONTENT', 'Content')),
                new TabSet('Settings', _t(ErrorPageItemRequestHandler::class . '.SETTINGS', 'Settings'))
            )->setTemplate('SilverStripe\\Forms\\CMSTabSet')
        ));

        $form->Fields()->findOrMakeTab('Root.Content')->setChildren($editFields);
        $form->Fields()->findOrMakeTab('Root.Settings')->setChildren($this->record->getSettingsFields()->setForm($form)->fieldByName('Root')->Tabs());



        // Reload the data since we added the settings fields
        $form->loadDataFrom($this->record, ($this->record->ID == 0 ? Form::MERGE_IGNORE_FALSEISH : Form::MERGE_DEFAULT));


        $form->Fields()->dataFieldByName('ParentID')->addExtraClass('parent-id-field');
        $form->Fields()->dataFieldByName('ViewerGroups')->addExtraClass('viewer-groups-field');
        $form->Fields()->dataFieldByName('EditorGroups')->addExtraClass('editor-groups-field');


        $form->setActions($this->record->getCMSActions()->setForm($form));
        $actionsFlattened = $form->Actions()->dataFields();
        if ($actionsFlattened) {
            foreach ($actionsFlattened as $action) {
                $action->setUseButtonTag(true);
            }
        }

        if ($this->record->exists() && $this->record->canEdit() && $this->record->canCreate()) {
            $form->Actions()->insertBefore(
                'ActionMenus',
                FormAction::create(
                    'doDuplicate',
                    _t(ErrorPageItemRequestHandler::class . '.DUPLICATE', '_Duplicate')
                )->setAttribute('data-icon', 'no-icon')->setUseButtonTag(true)
            );
        }

        $form->disableDefaultAction();

        $form->addExtraClass('ErrorPage-edit ErrorPageItemRequestHandler');
        $form->setAttribute(
            'data-history-link',
            Controller::join_links(
                LeftAndMain::config()->url_base,
                CMSPageHistoryViewerController::config()->url_segment,
                'show',
                $this->record->ID
            )
        );


        // Add the navigator if it doesn't exist
        $class = SilverStripeNavigator::class;
        if (!$form->Fields()->fieldByName($class)) {
            $navField = LiteralField::create($class, $this->getSilverStripeNavigator())->setForm($form)->setAllowHTML(true);
            $form->Fields()->push($navField);

            $form->addExtraClass('cms-previewable');
        }


        Requirements::css('webbuilders-group/silverstripe-siteconfig-error-pages:css/ErrorPageItemRequestHandler.css');
        Requirements::javascript('webbuilders-group/silverstripe-siteconfig-error-pages:javascript/ErrorPageItemRequestHandler.js');

        return $form;
    }

    /**
     * Handles request to edit the error page, if we're creating a new error page it is saved then redirected to editing that error page
     * @param SS_HTTPRequest $request HTTP Request Object
     * @return SS_HTTPResponse
     */
    public function edit($request)
    {
        if ($this->record && !$this->record->exists()) {
            $addController = CMSMain::create();
            $this->record = $addController->getNewItem('new-' . $this->config()->error_page_class, false);

            $form = $this->ItemEditForm();
            $this->extend('updateDoAdd', $this->record, $form);

            $this->record->Sort = DB::query('SELECT MAX("Sort") FROM "SiteTree" WHERE "ParentID" = 0')->value() + 1;

            try {
                $this->record->write();
            } catch (ValidationException $ex) {
                $form->setSessionValidationResult($ex->getResult());

                return $this->getToplevelController()->getResponseNegotiator()->respond($this->getRequest());
            }

            return $this->getToplevelController()->redirect(Controller::join_links($this->Link('edit'), (class_exists('Translatable') ? '?Locale=' . $this->record->Locale : '')));
        }


        return parent::edit($request);
    }

    /**
     * Handles saving the submission
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     * @see self::doSave()
     */
    public function save($data, Form $form)
    {
        return $this->doSave($data, $form);
    }

    /**
     * Save and Publish page handler
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function doSave($data, $form)
    {
        $record = $this->record;
        $controller = $this->getToplevelController();

        // Check publishing permissions
        $doPublish = !empty($data['publish']);
        if ($record && $doPublish && !$record->canPublish()) {
            return Security::permissionFailure($this);
        }

        // TODO Coupling to SiteTree
        $record->HasBrokenLink = 0;
        $record->HasBrokenFile = 0;

        if (!$record->ObsoleteClassName) {
            $record->writeWithoutVersion();
        }

        // Update the class instance if necessary
        if (isset($data['ClassName']) && $data['ClassName'] != $record->ClassName) {
            // Replace $record with a new instance of the new class
            $newClassName = $data['ClassName'];
            $record = $record->newClassInstance($newClassName);
        }

        // save form data into record
        $form->saveInto($record);
        $record->write();

        // If the 'Publish' button was clicked, also publish the page
        if ($doPublish) {
            $record->publishRecursive();
            $message = _t(
                CMSMain::class . '.PUBLISHED',
                "Published '{title}' successfully.",
                ['title' => $record->Title]
            );

            // Reload the object to avoid confusing the actions
            $this->record = ErrorPage::get()->byId($this->record->ID);
        } else {
            $message = _t(
                CMSMain::class . '.SAVED',
                "Saved '{title}' successfully.",
                ['title' => $record->Title]
            );
        }

        // Set form message
        $form->sessionMessage($message, 'good');

        if ($this->gridField->getList()->byId($this->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL = $controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }

    /**
     * Processes reverting staging to match the live site
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return HTTPResponse
     * @uses SiteTree->doRevertToLive()
     * @throws HTTPResponse_Exception
     */
    public function rollback($data, Form $form)
    {
        $recordID = $this->record->ID;
        $record = Versioned::get_one_by_stage(SiteTree::class, Versioned::LIVE, ['"SiteTree_Live"."ID"' => $recordID]);
        $controller = $this->getToplevelController();


        // a user can restore a page without publication rights, as it just adds a new draft state
        // (this action should just be available when page has been "deleted from draft")
        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        if (!$record || !$record->ID) {
            throw new HTTPResponse_Exception("Bad record ID #$recordID", 404);
        }

        $record->doRevertToLive();

        $form->sessionMessage(_t(
            CMSMain::class . '.RESTORED',
            "Restored '{title}' successfully",
            'Param {title} is a title',
            ['title' => $record->Title]
        ), 'good');

        if ($this->record) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL = $controller->removeAction($data['url']);
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
    public function archive($data, Form $form)
    {
        $record = $this->record;
        if (!$record || !$record->exists()) {
            throw new HTTPResponse_Exception('Bad record ID #' . $record->ID, 404);
        }

        if (!$record->canDelete()) {
            return Security::permissionFailure();
        }

        // Archive record
        $record->doArchive();

        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            $backForm = $toplevelController->getEditForm();
            $backForm->sessionMessage(_t(ErrorPageItemRequestHandler::class . '.ARCHIVEDPAGE', "Archived error page '{title}'", ['title' => $record->Title]), 'good');
        } else {
            $form->sessionMessage(_t(ErrorPageItemRequestHandler::class . '.ARCHIVEDPAGE', "Archived error page '{title}'", ['title' => $record->Title]), 'good');
        }

        // when an item is deleted, redirect to the parent controller
        $controller = $this->getToplevelController();
        $controller->getRequest()->addHeader('X-Pjax', 'Content'); // Force a content refresh

        return $controller->redirect($this->getBacklink(), 302); // redirect back to admin section
    }

    /**
     * Handles publishing the error page
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     * @see self::doSave()
     */
    public function publish($data, Form $form)
    {
        $data['publish'] = '1';

        return $this->doSave($data, $form);
    }

    /**
     * Handles unpublishing the error page
     * @param array $data Submitted Data
     * @param Form $form Submitting Form
     * @return SS_HTTPResponse
     */
    public function unpublish($data, Form $form)
    {
        $record = $this->record;
        $controller = $this->getToplevelController();

        if ($record && !$record->canUnpublish()) {
            return Security::permissionFailure($this);
        }

        if (!$record || !$record->ID) {
            throw new HTTPResponse_Exception("Bad record ID #" . (int) $data['ID'], 404);
        }

        $record->doUnpublish();

        $form->sessionMessage(_t('SilverStripe\\CMS\\Controllers\\CMSMain.REMOVEDPAGE', "Removed '{title}' from the published site", ['title' => $record->Title]), 'good');
        if ($this->gridField->getList()->byId($this->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL = $controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }

    /**
     * Handles duplicating an error page
     * @param array $data Submitted Data
     * @param Form $form Submitted Form
     * @return SS_HTTPResponse
     */
    public function doDuplicate($data, Form $form)
    {
        $record = $this->record;
        $controller = $this->getToplevelController();

        if ($record && (!$record->canEdit() || !$record->canCreate())) {
            return Security::permissionFailure($this);
        }

        if (!$record || !$record->ID) {
            throw new HTTPResponse_Exception("Bad record ID #" . (int) $data['ID'], 404);
        }

        $this->record = $record->duplicate();

        $form->sessionMessage(_t('SilverStripe\\CMS\\Controllers\\CMSMain.DUPLICATED', "Duplicated '{title}' successfully", ['title' => $record->Title]), 'good');

        if ($this->record) {
            return $this->getToplevelController()->redirect(Controller::join_links($this->Link('edit'), (class_exists('Translatable') ? '?Locale=' . $this->record->Locale : '')));
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL = $controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }

    /**
     * Wrapper to redirect back
     * @return HTTPResponse
     */
    public function redirectBack(): HTTPResponse
    {
        return $this->getToplevelController()->redirectBack();
    }

    /**
     * Used for preview controls, mainly links which switch between different states of the page.
     * @return ArrayData
     */
    protected function getSilverStripeNavigator($segment = null)
    {
        if ($this->record) {
            $class = SilverStripeNavigator::class;
            $navigator = new $class($this->record);
            return $navigator->renderWith(singleton(CMSMain::class)->getTemplatesWithSuffix('_SilverStripeNavigator'));
        } else {
            return false;
        }
    }

    /**
     * Gets the preview link
     * @return string Link to view the record
     */
    public function LinkPreview()
    {
        return $this->record->Link();
    }
}
