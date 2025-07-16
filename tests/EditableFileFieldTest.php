<?php

namespace NSWDPC\FileTypeManagement\Tests;

use NSWDPC\FileTypeManagement\Extensions\EditableFileFieldExtension;
use NSWDPC\FileTypeManagement\Extensions\FileTypeHandlingExtension;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\ValidationException;
use SilverStripe\UserForms\Model\EditableFormField\EditableFileField;

/**
 * Test editable file field handling
 */
class EditableFileFieldTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected $runTests = false;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists(EditableFileField::class)) {
            $this->markTestSkipped(
                'silverstripe/userforms is required for this test',
            );
        }

        // Set basic image + document allowed extensions
        Config::modify()->set(
            File::class,
            'allowed_extensions',
            ['jpg', 'jpeg', 'png','gif', 'doc', 'docx', 'pdf']
        );
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

        /** @phpstan-ignore class.notFound */
        $field = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        $selectedExtensions = ['jpg','png'];
        $field->SelectedFileTypes = json_encode($selectedExtensions);

        $id = $field->write();

        /** @phpstan-ignore class.notFound */
        $checkField = EditableFileField::get()->byId($id);
        $this->assertTrue($checkField->hasExtension(EditableFileFieldExtension::class));
        // @phpstan-ignore method.notFound
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

        /** @phpstan-ignore class.notFound */
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
            /** @phpstan-ignore method.impossibleType */
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

        /** @phpstan-ignore class.notFound */
        $field = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        // this must trigger default
        $field->SelectedFileTypes = null;

        $id = $field->write();

        /** @phpstan-ignore class.notFound */
        $checkField = EditableFileField::get()->byId($id);
        $this->assertTrue($checkField->hasExtension(EditableFileFieldExtension::class));
        // @phpstan-ignore method.notFound
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

        /** @phpstan-ignore class.notFound */
        $editableField = EditableFileField::create([
            'Title' => 'JPG PNG Field',
            'FolderID' => $folderId
        ]);

        // write same types to field
        $editableField->SelectedFileTypes = json_encode($types);
        $editableField->write();


        // Retrieve validator
        $formField = $editableField->getFormField();
        $this->assertInstanceOf(FileHandleField::class, $formField);
        $allowedExtensions = $formField->getAllowedExtensions();

        // All types should be selectable
        $this->assertEquals($types, $allowedExtensions);

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
        $this->assertInstanceOf(FileHandleField::class, $formField);
        $updatedAllowedExtensions = $formField->getAllowedExtensions();

        // zip should be disallowed
        $this->assertEquals($updatedTypes, $updatedAllowedExtensions);
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

        /** @phpstan-ignore class.notFound */
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
            /** @phpstan-ignore class.notFound */
            EditableFileField::class,
            'allowed_extensions_blacklist',
            $toBeDeniedExtension
        );

        // get validator allowed extensions
        /** @phpstan-ignore class.notFound */
        $checkField = EditableFileField::get()->byId($id);
        $formField = $checkField->getFormField();
        $this->assertInstanceOf(FileHandleField::class, $formField);
        $updatedAllowedExtensions = $formField->getAllowedExtensions();

        $this->assertEquals(
            $baseExtensions, // only base extensions should be allowed
            $updatedAllowedExtensions
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

        /** @phpstan-ignore class.notFound */
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
            /** @phpstan-ignore class.notFound */
            EditableFileField::class,
            'allowed_extensions_blacklist',
            $toBeDeniedExtensionList
        );

        try {
            $id = $editableField->write();
            /** @phpstan-ignore method.impossibleType */
            $this->assertTrue(false, "Write should have triggered validation exception");
        } catch (ValidationException $validationException) {
            // exception handling
            $this->assertStringContainsString($toBeDeniedExtension, $validationException->getMessage());
        }
    }
}
