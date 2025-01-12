# Documentation

## Configure File.allowed_extensions

If further restriction of the default allowed file types is required, a system administrator [can configure acceptable values via `File.allowed_extensions`](https://docs.silverstripe.org/en/5/developer_guides/files/allowed_file_types/) in your project's YAML configuration.

This example sets a highly restrictive allow list of extensions in a project's app/_config.php:

```php
<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Assets\File;

Config::modify()->set(
    File::class,
    'allowed_extensions',
    [
        'jpg','jpeg','gif','png',
    ]
);
```

> Note: any previously uploaded file having an extension not in the updated array of allowed_extensions will not be viewable.

## Usage

With the module installed, in Settings > Uploads, choose a subset (or all) of the selectable file extensions.

## Editable file field support

If you have [silverstripe/userforms](https://github.com/silverstripe/silverstripe-userforms) installed, the module will provide a file type selection field on the `EditableFileField` dataobject.

The form editor should select the allowed file types for that field from the provided list.

## Other dataobjects

If you have an UploadModel DataObject that controls a file input field, you can apply the FileTypeHandlingExtension to it:

```yml
---
Name: app-filetypes
---
My\App\UploadModel:
  extensions:
    - 'NSWDPC\FileTypeManagement\Extensions\FileTypeHandlingExtension'
```

Use the return value from `getExtensionsForValidator()` to configure the allowed file types for the FileField.

## Defaults

If no file types are configured, the default file type used is `txt`.

## Updates

If the master list of allowed file types in site settings is updated, the file types returned from the method `getExtensionsForValidator()` will reflect this change (e.g. to remove an allowed file type)
