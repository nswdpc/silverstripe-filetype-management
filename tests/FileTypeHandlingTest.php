<?php

namespace NSWDPC\FileTypeManagement\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\ValidationException;

/**
 * Test upload page field creation
 */
class FileTypeHandlingTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function setUp(): void
    {
        parent::setUp();

        // Set basic image + document allowed extensions
        Config::modify()->set(
            File::class,
            'allowed_extensions',
            ['jpg', 'jpeg', 'png','gif', 'doc', 'docx', 'pdf']
        );
    }

    /**
     * Test that the stored extensions are returned as expected
     */
    public function testSiteConfigRestriction(): void
    {
        // Using static config in setUp()
        $allowed = ['jpg','jpeg','png','gif'];
        $config = SiteConfig::current_site_config();
        // set a default set of images
        $config->AllowedFileExtensions = $allowed;
        $config->write();

        $newConfig = SiteConfig::current_site_config();
        $storedAllowed = $newConfig->getFilteredAllowedExtensions();
        $this->assertEquals($allowed, $storedAllowed, "Expected allowed and stored allowed are the same");
    }

    /**
     * Test validation of extra file type not in allowed file types
     */
    public function testSiteConfigBadRestriction(): void
    {
        // Using static config in setUp()
        $images = ['jpg','png'];
        $extraType = "xyz123";
        $allowed = array_merge($images, [ $extraType ]);
        $config = SiteConfig::current_site_config();
        // set a default set of images
        $config->AllowedFileExtensions = $allowed;
        try {
            $config->write();
            $this->assertTrue(false, "Write should have triggered validation exception");
        } catch (ValidationException $validationException) {
            // exception handling
            $this->assertStringContainsString($extraType, $validationException->getMessage());
        }
    }

}
