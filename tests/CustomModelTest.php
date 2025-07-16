<?php

namespace NSWDPC\FileTypeManagement\Tests;

use NSWDPC\FileTypeManagement\Extensions\FileTypeHandlingExtension;
use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FileField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Test custom model file type handling
 */
class CustomModelTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        CustomModel::class,
    ];

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
     * Test setting restricted file types on a field
     */
    public function testFileFieldRestriction(): void
    {

        // set restricted set of file types
        $images = ['jpg','jpeg','png','gif'];
        $config = SiteConfig::current_site_config();
        // set a default set of images
        $config->AllowedFileExtensions = $images;
        $config->write();

        $model = CustomModel::create();
        $this->assertTrue($model->hasExtension(FileTypeHandlingExtension::class));

        // set a list of extensions
        $selectedExtensions = ['jpg','png'];
        $model->SelectedFileTypes = json_encode($selectedExtensions);
        $model->write();

        $field = FileField::create(
            'TestFileField',
            'Test file field'
        );

        $allowedfileTypes = $model->getExtensionsForValidator();
        sort($allowedfileTypes);

        $validator = $field->getValidator();
        $validator->setAllowedExtensions($allowedfileTypes);

        $allowed = $validator->getAllowedExtensions();
        sort($allowed);

        $this->assertEquals(
            $allowedfileTypes,
            $allowed
        );

    }

    /**
     * Test setting invalid file type on model
     */
    public function testCustomModelInvalidFileType(): void
    {

        try {

            $msg = "";
            // set restricted set of file types
            $images = ['jpg','jpeg','png','gif'];
            $config = SiteConfig::current_site_config();
            // set a default set of images
            $config->AllowedFileExtensions = $images;
            $config->write();

            $model = CustomModel::create();
            // set a list of extensions
            $selectedExtensions = ['jpg','png', 'foo'];
            $model->SelectedFileTypes = json_encode($selectedExtensions);
            $model->write();

        } catch (\SilverStripe\Core\Validation\ValidationException) {
            $msg = "exception thrown";
        }

        $this->assertEquals("exception thrown", $msg);
    }

}
