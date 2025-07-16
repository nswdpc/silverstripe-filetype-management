<?php

namespace NSWDPC\FileTypeManagement\Extensions;

use NSWDPC\FileTypeManagement\Models\Configuration;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\Forms\FormField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;

/**
 * Support EditableFileField handling
 * @author James
 */
class EditableFileFieldExtension extends FileTypeHandlingExtension
{

    /**
     * Update form field with extension requirements set via upload Validator
     * attached to the FileHandleField
     * See: EditableFormField::doUpdateFormField()
     * @param $field FileHandleField
     */
    public function afterUpdateFormField(FileHandleField &$field)
    {
        if($field instanceof FormField) {
            $extensions = $this->getExtensionsForValidator();
            $field->setAllowedExtensions($extensions);

            // set a right title on the field showing valid files
            $rightTitle = $field->RightTitle();
            $fileTypesSuffix = _t(
                self::class . '.PUBLIC_FILE_LIST',
                'Allowed types: {types}',
                [
                    'types' => implode(", ", array_values($extensions))
                ]
            );
            $field->setRightTitle(trim($rightTitle . "\n" . $fileTypesSuffix));
        }
    }
}
