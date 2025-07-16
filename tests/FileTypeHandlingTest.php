<?php

namespace NSWDPC\FileTypeManagement\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\ValidationException;

/**
 * Test generic file type handling
 */
class FileTypeHandlingTest extends SapphireTest
{
    protected $usesDatabase = true;

    #[\Override]
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
            /** @phpstan-ignore method.impossibleType */
            $this->assertTrue(false, "Write should have triggered validation exception");
        } catch (\SilverStripe\Core\Validation\ValidationException $validationException) {
            // exception handling
            $this->assertStringContainsString($extraType, $validationException->getMessage());
        }
    }

}
