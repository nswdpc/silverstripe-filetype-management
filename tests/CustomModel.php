<?php

namespace NSWDPC\FileTypeManagement\Tests;

use NSWDPC\FileTypeManagement\Extensions\FileTypeHandlingExtension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Custom model for tests
 */
class CustomModel extends DataObject implements TestOnly
{

    private static $extensions = [
        FileTypeHandlingExtension::class
    ];

}
