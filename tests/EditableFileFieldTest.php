<?php

namespace NSWDPC\FileTypeManagement\Tests;

use NSWDPC\FileTypeManagement\Extensions\FileTypeHandlingExtension;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\ValidationException;
use SilverStripe\UserForms\Model\EditableFormField\EditableFileField;

/**
 * Test upload page field creation
 */
class EditableFileFieldTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected $runTests = false;

    public function setUp(): void
    {
        parent::setUp();

        // Set basic image + document allowed extensions
        Config::modify()->set(
            File::class,
            'allowed_extensions',
            ['jpg', 'jpeg', 'png','gif', 'doc', 'docx', 'pdf']
        );

        if(!class_exists(EditableFileField::class)) {
            $this->markTestSkipped(
                'silverstripe/userforms is required for this test',
            );
        }
    }

    /**
     * Test setting a field restriction returns the same
     */
    public function testFieldRestriction(): void
    {

        $folder = Folder::create([
            'Title' => 'Test folder',
        ]);
        $folderId = $folder->write();

        $images = ['jpg','jpeg','png','gif'];
        $config = SiteConfig::current_site_config();
        // set a default set of images
        $config->AllowedFileExtensions = $images;
        $config->write();

        $field = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        $selectedExtensions = ['jpg','png'];
        $field->SelectedFileTypes = json_encode($selectedExtensions);

        $id = $field->write();

        $checkField = EditableFileField::get()->byId($id);
        $extensionsForValidator = $checkField->getExtensionsForValidator();

        $this->assertEquals(
            $selectedExtensions,
            $extensionsForValidator
        );
    }

    /**
     * Test adding extra type to field triggers validation exception
     */
    public function testFieldBadRestriction(): void
    {

        $folder = Folder::create([
            'Title' => 'Test folder',
        ]);
        $folderId = $folder->write();

        $images = ['jpg','jpeg','png','gif'];
        $config = SiteConfig::current_site_config();
        // set a default set of images
        $config->AllowedFileExtensions = $images;
        $config->write();

        $field = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        $extraType = "abc999";
        $images = ['jpg','png'];
        $exts = array_merge($images, [ $extraType ]);
        $field->SelectedFileTypes = json_encode($exts);

        try {
            $id = $field->write();
            $this->assertTrue(false, "Write should have triggered validation exception");
        } catch (ValidationException $validationException) {
            // exception handling
            $this->assertStringContainsString($extraType, $validationException->getMessage());
        }
    }

    public function testFieldNoRestriction(): void
    {

        $folder = Folder::create([
            'Title' => 'Test folder',
        ]);
        $folderId = $folder->write();

        $images = ['jpg','jpeg','png','gif'];
        $config = SiteConfig::current_site_config();
        // set a default set of images
        $config->AllowedFileExtensions = $images;
        $config->write();

        $field = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        // this must trigger default
        $field->SelectedFileTypes = null;
        $id = $field->write();

        $checkField = EditableFileField::get()->byId($id);

        $extensions = $checkField->getExtensionsForValidator();
        $defaults = FileTypeHandlingExtension::getDefaultAllowedFileExtensions();

        $this->assertEquals(
            $defaults,
            $extensions,
            "Extensions should match defaults"
        );
    }

    /**
     * Test a field with stored configuration, with a static configuration change
     * made to the allowed file extensions
     */
    public function testFieldAfterConfigurationChange(): void
    {
        // Types for this system
        $types = ['jpg', 'jpeg', 'png','gif', 'zip'];
        // Set basic image + document allowed extensions
        Config::modify()->set(
            File::class,
            'allowed_extensions',
            $types
        );

        // write same types to config
        $config = SiteConfig::current_site_config();
        $config->AllowedFileExtensions = $types;
        $config->write();

        // Editable field setup
        $folder = Folder::create([
            'Title' => 'Test folder',
        ]);
        $folderId = $folder->write();

        $editableField = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        // write same types to field
        $editableField->SelectedFileTypes = json_encode($types);
        $editableField->write();


        // Retrieve validator
        $formField = $editableField->getFormField();
        $validator = $formField->getValidator();
        $allowedExtensionsFromValidator = $validator->getAllowedExtensions();

        // All types should be selectable
        $this->assertEquals($types, $allowedExtensionsFromValidator);

        // drop zip from static config
        $updatedTypes = ['jpg', 'jpeg', 'png','gif'];
        // store in File static config
        Config::modify()->set(
            File::class,
            'allowed_extensions',
            $updatedTypes
        );

        // retrieve the form field again
        $formField = $editableField->getFormField();
        $validator = $formField->getValidator();
        $updatedAllowedExtensionsFromValidator = $validator->getAllowedExtensions();

        // zip should be disallowed
        $this->assertEquals($updatedTypes, $updatedAllowedExtensionsFromValidator);
    }

    public function testFieldDenyList(): void
    {
        $folder = Folder::create([
            'Title' => 'Test folder',
        ]);
        $folderId = $folder->write();

        $images = ['jpg','jpeg','png','gif'];
        $config = SiteConfig::current_site_config();
        // set a default set of images
        $config->AllowedFileExtensions = $images;
        $config->write();

        $editableField = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        $baseExtensions = ['jpg','png'];
        $toBeDeniedExtension = ['gif'];
        $selectedExtensions = array_merge($baseExtensions, $toBeDeniedExtension);
        $editableField->SelectedFileTypes = json_encode($selectedExtensions);

        $id = $editableField->write();

        // Config change to block gif
        Config::modify()->set(
            EditableFileField::class,
            'allowed_extensions_blacklist',
            $toBeDeniedExtension
        );

        // get validator allowed extensions
        $checkField = EditableFileField::get()->byId($id);
        $formField = $checkField->getFormField();
        $validator = $formField->getValidator();
        $updatedAllowedExtensionsFromValidator = $validator->getAllowedExtensions();

        $this->assertEquals(
            $baseExtensions, // only base extensions should be allowed
            $updatedAllowedExtensionsFromValidator
        );
    }

    public function testWriteFieldWithDeniedExtension(): void
    {
        $toBeDeniedExtension = "den123";

        // Set basic image + document allowed extensions
        Config::modify()->set(
            File::class,
            'allowed_extensions',
            ['jpg', 'jpeg', 'png','gif', 'doc', 'docx', 'pdf', $toBeDeniedExtension]
        );

        $folder = Folder::create([
            'Title' => 'Test folder',
        ]);
        $folderId = $folder->write();

        // these images are allowed
        $images = ['jpg','jpeg','png','gif', $toBeDeniedExtension];
        $config = SiteConfig::current_site_config();
        // set a default set of images
        $config->AllowedFileExtensions = $images;
        $config->write();

        $editableField = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        $baseExtensions = ['jpg','png'];
        $toBeDeniedExtensionList = [ $toBeDeniedExtension ];
        $selectedExtensions = array_merge($baseExtensions, $toBeDeniedExtensionList);
        $editableField->SelectedFileTypes = json_encode($selectedExtensions);

        // Config change to block gif
        Config::modify()->set(
            EditableFileField::class,
            'allowed_extensions_blacklist',
            $toBeDeniedExtensionList
        );

        try {
            $id = $editableField->write();
            $this->assertTrue(false, "Write should have triggered validation exception");
        } catch (ValidationException $validationException) {
            // exception handling
            $this->assertStringContainsString($toBeDeniedExtension, $validationException->getMessage());
        }
    }
}
