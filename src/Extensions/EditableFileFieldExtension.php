<?php

namespace NSWDPC\FileTypeManagement\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\Forms\FormField;
use SilverStripe\UserForms\Model\EditableFormField\EditableFileField;

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
        if ($field instanceof FormField) {
            $extensions = $this->getExtensionsForValidator();
            // further restrict by allowed extensions already set
            $allowedExtensions = $field->getAllowedExtensions();
            $extensions = array_intersect($extensions, $allowedExtensions);
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

    /**
     * @inheritdoc
     * Include removal of extensions found in EditableFileField.allowed_extensions_blacklist
     */
    #[\Override]
    protected function getFilteredAllowedExtensions(): array
    {
        $filteredAllowedExtensions = parent::getFilteredAllowedExtensions();
        $deniedExtensions = Config::inst()->get(EditableFileField::class, 'allowed_extensions_blacklist');
        if(is_array($deniedExtensions) && $deniedExtensions != []) {
            return array_diff($filteredAllowedExtensions, $deniedExtensions);
        } else {
            return $filteredAllowedExtensions;
        }
    }
}
