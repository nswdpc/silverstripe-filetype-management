<?php

namespace NSWDPC\FileTypeManagement\Extensions;

use NSWDPC\FileTypeManagement\Models\Configuration;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;

/**
 * Handle file types in field management
 * Apply this extension to a data model that requires file type restrictions
 * For EditableFileField handling, see EditableFileFieldExtension
 * @author James
 * @property ?string $SelectedFileTypes
 * @extends \SilverStripe\ORM\DataExtension<static>
 */
class FileTypeHandlingExtension extends DataExtension
{
    /**
     * @config
     */
    private static array $db = [
        'SelectedFileTypes' => 'Text'
    ];

    /**
     * Validate input
     */
    #[\Override]
    public function validate(ValidationResult $validationResult)
    {
        // the filtered list of allowed file types
        $types = $this->getFilteredAllowedExtensions();
        // the selected file types
        // @phpstan-ignore property.notFound
        $extensions = $this->getOwner()->SelectedFileTypes;
        if (is_string($extensions) && $extensions !== '') {
            $extensions = json_decode($extensions, true);
            $diff = array_diff($extensions, $types);
            if ($diff !== []) {
                $validationResult->addFieldError(
                    'SelectedFileTypes',
                    _t(
                        self::class . '.DISALLOWED_TYPES',
                        'The following types are disallowed: {types}',
                        [
                            'types' => implode(", ", $diff)
                        ]
                    ),
                    ValidationResult::TYPE_ERROR
                );
            }
        }
    }

    /**
     * Return the extensions to be used for the upload validator on the FileHandleField
     * The allowed extensions are:
     * - those selected for this field AND
     * - are in the current list of filtered, allowed, extensions
     */
    public function getExtensionsForValidator(): array
    {
        $filteredAllowed = $this->getFilteredAllowedExtensions();
        if (count($filteredAllowed) == 0) {
            // nothing is allowed, use default
            return self::getDefaultAllowedFileExtensions();
        }

        // Selected extensions
        // @phpstan-ignore property.notFound
        $extensions = $this->getOwner()->SelectedFileTypes;
        // string values are stored JSON encoded by CheckboxsetField
        $extensions = json_decode($extensions ?? '', true);

        if (!is_array($extensions)) {
            // invalid value, default to empty
            $extensions = [];
        }

        // Get extensions for this field, that are also in the $filteredAllowed list
        $extensionsForValidator = array_intersect($extensions, $filteredAllowed);
        if (count($extensionsForValidator) == 0) {
            $extensionsForValidator = self::getDefaultAllowedFileExtensions();
        }

        return $extensionsForValidator;
    }

    /**
     * The default allowed extension, if none are configured
     */
    public static function getDefaultAllowedFileExtensions(): array
    {
        return ['txt'];
    }

    /**
     * Return filtered list of allowed file extensions from SiteConfig that could be selected
     * The return value could be empty
     */
    protected function getFilteredAllowedExtensions(): array
    {
        $config = SiteConfig::current_site_config();
        $types = $config->getFilteredAllowedExtensions();
        $source = [];
        if (is_array($types) && $types !== []) {
            // drop types that are denied via configuration
            $denyList = Config::inst()->get(Configuration::class, 'allowed_extensions_denylist');
            if (is_array($denyList) && $denyList !== []) {
                // return types not present in $denyList
                $types = array_diff($types, $denyList);
            }

            // Create a source list for selection
            foreach ($types as $type) {
                $source[ $type ] = $type;
            }
        }

        return $source;
    }


    /**
     * Update fields
     */
    #[\Override]
    public function updateCmsFields(FieldList $fields)
    {
        $source = $this->getFilteredAllowedExtensions();
        $default = self::getDefaultAllowedFileExtensions();
        if (count($source) == 0) {
            $description = _t(
                self::class . '.NO_CONFIGURED_FILE_TYPES',
                'There are no configured file types. Please ask an administrator to set these up.'
                . '<br>The field will be restricted to these file types until this is done: <code>{types}</code>',
                [
                    'types' => implode(", ", array_values($default))
                ]
            );
        } else {
            $description = _t(
                self::class . '.IF_NOTHING_SELECTED_DEFAULT_FILE_TYPES',
                'If nothing is selected, the field will be restricted to these file types: <code>{types}</code>',
                [
                    'types' => implode(", ", array_values($default))
                ]
            );
        }

        $selectorField = CheckboxsetField::create(
            'SelectedFileTypes',
            _t(
                self::class . '.SELECT_FILE_TYPES',
                'Select allowed file types'
            ),
            $source
        );
        if ($description) {
            $selectorField->setDescription($description);
        }

        $fields->addFieldToTab(
            'Root.Main',
            $selectorField
        );
    }
}
