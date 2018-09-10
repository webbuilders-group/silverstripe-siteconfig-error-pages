<?php

use SilverStripe\Security\SecurityToken;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Dev\FunctionalTest;
class ErrorPageItemRequestHandlerTest extends FunctionalTest {
    protected static $fixture_file='ErrorPageItemRequestHandlerTest.yml';
    
    /**
     * Tests creating an error page
     */
    public function testCreateNewErrorPage() {
        //Login as an Admin
        $this->logInWithPermission('ADMIN');
        
        
        //Attempt to create a new error page disabling follow redirect
        $this->autoFollowRedirection=false;
        $response=$this->get('admin/settings/EditForm/field/ErrorPages/item/new?SecurityID='.SecurityToken::getSecurityID());
        $this->autoFollowRedirection=true;
        
        
        //Verify we have a 302 response
        $this->assertEquals(302, $response->getStatusCode());
        
        
        //Verify we were directed to an id greater than 0
        $this->assertRegExp('/\/admin\/settings\/EditForm\/field\/ErrorPages\/item\/([1-9]\d*)($|\/edit)/', $response->getHeader('Location'));
        
        
        //Make sure exists on the draft site
        $errorPage=Versioned::get_by_stage(ErrorPage::class, 'Stage')
                                                                ->filter('ID:not', $this->objFromFixture(ErrorPage::class, 'page404')->ID)
                                                                ->first();
        
        $this->assertInstanceOf(ErrorPage::class, $errorPage, 'Page does not exist on the draft site and it should');
        $this->assertGreaterThan(0, $errorPage->ID, 'Page does not exist on the draft site and it should');
        $this->assertTrue($errorPage->exists(), 'Page does not exist on the draft site and it should');
        
        
        //Make sure it does not exist on the published site
        $this->assertFalse($errorPage->isPublished(), 'Page exists on live but should not');
    }
    
    /**
     * Tests unpublishing a published error page
     */
    public function testUnpublish() {
        //Login as an Admin
        $this->logInWithPermission('ADMIN');
        
        
        //Find and publish the error page
        $errorPage=$this->objFromFixture(ErrorPage::class, 'page404');
        $errorPage->publish('Stage', 'Live');
        
        
        //Make sure it exists on the published site
        $this->assertTrue($errorPage->isPublished(), 'Page does not exist on the live site and it should prior to unpublish');
        
        
        //Unpublish the page and don't follow the redirect
        $this->autoFollowRedirection=false;
        $response=$this->post('admin/settings/EditForm/field/ErrorPages/item/'.$errorPage->ID.'/ItemEditForm', array_merge($errorPage->toMap(), array(
                                                                                                                                                    'action_unpublish'=>'Unpublish',
                                                                                                                                                    'SecurityID'=>SecurityToken::getSecurityID()
                                                                                                                                                )));
        $this->autoFollowRedirection=true;
        
        
        //Verify the page still exists on the draft site
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Stage', '"SiteTree"."ID"='.$errorPage->ID);
        $this->assertInstanceOf(ErrorPage::class, $errorPage, 'Page does not exist on the draft site and it should');
        $this->assertGreaterThan(0, $errorPage->ID, 'Page does not exist on the draft site and it should');
        $this->assertTrue($errorPage->exists(), 'Page does not exist on the draft site and it should');
        
        
        //Make sure it no longer exists on the published site
        $this->assertFalse($errorPage->isPublished(), 'Page exists on live but should not after unpublishing');
    }
    
    /**
     * Tests saving to the draft site without the page being published
     */
    public function testSaveDraft() {
        //Login as an Admin
        $this->logInWithPermission('ADMIN');
        
        
        //Find the error page
        $errorPage=$this->objFromFixture(ErrorPage::class, 'page404');
        

        $this->autoFollowRedirection=false;
        $response=$this->post('admin/settings/EditForm/field/ErrorPages/item/'.$errorPage->ID.'/ItemEditForm', array_merge($errorPage->toMap(), array(
                                                                                                                                                    'ErrorCode'=>400,
                                                                                                                                                    'action_save'=>'Save Draft',
                                                                                                                                                    'SecurityID'=>SecurityToken::getSecurityID()
                                                                                                                                                )));
        $this->autoFollowRedirection=true;
        
        
        //Refetch the object
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Stage', '"SiteTree"."ID"='.$errorPage->ID);
        $this->assertInstanceOf(ErrorPage::class, $errorPage, 'Could not find the error page after saving');
        $this->assertTrue($errorPage->exists(), 'Could not find the error page after saving');
        
        
        //Verify the status code changed
        $this->assertEquals(400, $errorPage->ErrorCode);
    }
    
    /**
     * Tests publishing a page
     */
    public function testPublish() {
        //Login as an Admin
        $this->logInWithPermission('ADMIN');
        
        
        //Find the error page
        $errorPage=$this->objFromFixture(ErrorPage::class, 'page404');
        
        $this->assertFalse($errorPage->isPublished(), 'Page does exists on the live site and it should not prior to publish');
        
        
        //Publish the page and don't follow the redirect
        $this->autoFollowRedirection=false;
        $response=$this->post('admin/settings/EditForm/field/ErrorPages/item/'.$errorPage->ID.'/ItemEditForm', array_merge($errorPage->toMap(), array(
                                                                                                                                                    'action_publish'=>'Save & publish',
                                                                                                                                                    'SecurityID'=>SecurityToken::getSecurityID()
                                                                                                                                                )));
        $this->autoFollowRedirection=true;
        
        
        //Make sure the page published
        $this->assertTrue($errorPage->isPublished(), 'Page does not exist on the live site and it should after publishing');
    }
    
    /**
     * Tests saving to the draft site without affecting the published page
     */
    public function testSaveDraftWithPublished() {
        //Login as an Admin
        $this->logInWithPermission('ADMIN');
        
        
        //Find and publish the error page
        $errorPage=$this->objFromFixture(ErrorPage::class, 'page404');
        $errorPage->publish('Stage', 'Live');
        

        $this->autoFollowRedirection=false;
        $response=$this->post('admin/settings/EditForm/field/ErrorPages/item/'.$errorPage->ID.'/ItemEditForm', array_merge($errorPage->toMap(), array(
                                                                                                                                                    'ErrorCode'=>400,
                                                                                                                                                    'action_save'=>'Save Draft',
                                                                                                                                                    'SecurityID'=>SecurityToken::getSecurityID()
                                                                                                                                                )));
        $this->autoFollowRedirection=true;
        
        
        //Refetch the object
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Stage', '"SiteTree"."ID"='.$errorPage->ID);
        $this->assertInstanceOf(ErrorPage::class, $errorPage, 'Could not find the error page after saving');
        $this->assertTrue($errorPage->exists(), 'Could not find the error page after saving');
        
        
        //Verify the status code changed
        $this->assertEquals(400, $errorPage->ErrorCode);
        
        
        //Fetch the live instance
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Live', '"SiteTree"."ID"='.$errorPage->ID);
        $this->assertInstanceOf(ErrorPage::class, $errorPage, 'Could not find the error page on the live site after saving');
        $this->assertTrue($errorPage->exists(), 'Could not find the error page on the live site after saving');
        
        
        //Verify the status code not changed
        $this->assertEquals(404, $errorPage->ErrorCode);
    }
    
    /**
     * Tests archiving a published page, which should unpublish and delete the draft of the page
     */
    public function testArchivePublished() {
        //Login as an Admin
        $this->logInWithPermission('ADMIN');
        
        
        //Find and publish the error page
        $errorPage=$this->objFromFixture(ErrorPage::class, 'page404');
        $errorPage->publish('Stage', 'Live');
        $pageID=$errorPage->ID;
        
        
        //Make sure it exists on the published site
        $this->assertTrue($errorPage->isPublished(), 'Page does not exist on the live site and it should prior to archiving');
        
        
        //Archive the page and don't follow the redirect
        $this->autoFollowRedirection=false;
        $response=$this->post('admin/settings/EditForm/field/ErrorPages/item/'.$errorPage->ID.'/ItemEditForm', array_merge($errorPage->toMap(), array(
                                                                                                                                                    'action_archive'=>'Archive',
                                                                                                                                                    'SecurityID'=>SecurityToken::getSecurityID()
                                                                                                                                                )));
        $this->autoFollowRedirection=true;
        
        
        //Verify the page still exists on the draft site
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Stage', '"SiteTree"."ID"='.$pageID);
        $this->assertNull($errorPage, 'Page exists exist on the draft site and it should not after archiving');
        
        
        //Make sure it no longer exists on the published site
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Live', '"SiteTree"."ID"='.$pageID);
        $this->assertNull($errorPage, 'Page exists exist on the live site and it should not after archiving');
    }
    
    /**
     * Tests archiving just a draft page
     */
    public function testArchiveDraft() {
        //Login as an Admin
        $this->logInWithPermission('ADMIN');
        
        
        //Find and publish the error page
        $errorPage=$this->objFromFixture(ErrorPage::class, 'page404');
        $pageID=$errorPage->ID;
        
        
        //Archive the page and don't follow the redirect
        $this->autoFollowRedirection=false;
        $response=$this->post('admin/settings/EditForm/field/ErrorPages/item/'.$errorPage->ID.'/ItemEditForm', array_merge($errorPage->toMap(), array(
                                                                                                                                                    'action_archive'=>'Archive',
                                                                                                                                                    'SecurityID'=>SecurityToken::getSecurityID()
                                                                                                                                                )));
        $this->autoFollowRedirection=true;
        
        
        //Verify the page still exists on the draft site
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Stage', '"SiteTree"."ID"='.$pageID);
        $this->assertNull($errorPage, 'Page exists exist on the draft site and it should not after archiving');
    }
    
    /**
     * Tests reverting the error page to the published version
     */
    public function testRevertToPublished() {
        //Login as an Admin
        $this->logInWithPermission('ADMIN');
        
        
        //Purge Versioned
        Versioned::reset();
        
        //Fetch the error page, publish it, then change the error code
        $errorPage=$this->objFromFixture(ErrorPage::class, 'page404');
        $errorPage->publish('Stage', 'Live');
        $errorPage->ErrorCode=400;
        $errorPage->write();
        
        
        //Re-fetch the error page
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Stage', '"SiteTree"."ID"='.$errorPage->ID);
        $this->assertEquals(400, $errorPage->ErrorCode); //Sanity check
        
        
        //Revert the page and don't follow the redirect
        $this->autoFollowRedirection=false;
        $response=$this->post('admin/settings/EditForm/field/ErrorPages/item/'.$errorPage->ID.'/ItemEditForm', array_merge($errorPage->toMap(), array(
                                                                                                                                                    'action_rollback'=>'Cancel draft changes',
                                                                                                                                                    'SecurityID'=>SecurityToken::getSecurityID()
                                                                                                                                                )));
        $this->autoFollowRedirection=true;
        
        
        //Re-fetch the error page and make sure the code switched back to 404
        $errorPage=Versioned::get_one_by_stage(ErrorPage::class, 'Stage', '"SiteTree"."ID"='.$errorPage->ID);
        $this->assertEquals(404, $errorPage->ErrorCode);
    }
}
?>