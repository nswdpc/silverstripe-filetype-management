<?php

namespace NSWDPC\FileTypeManagement\Extensions;

use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationResult;
use Symbiote\MultiValueField\Fields\MultiValueListField;
use Symbiote\MultiValueField\ORM\FieldType\MultiValueField;

/**
 * File type confguration handling
 * @author James
 * @property mixed $AllowedFileExtensions
 * @extends \SilverStripe\ORM\DataExtension<(\SilverStripe\SiteConfig\SiteConfig & static)>
 */
class SiteConfigExtension extends DataExtension
{
    /**
     * @config
     */
    private static array $db = [
        'AllowedFileExtensions' => MultiValueField::class
    ];

    /**
     * Get the allowed file extensions for this site, based on configuration
     */
    protected function getSystemAllowedFileTypes(): array
    {
        $allowed_extensions = File::getAllowedExtensions();
        if (!is_array($allowed_extensions)) {
            return [];
        } else {
            $exts = array_filter($allowed_extensions);
            $data = [];
            foreach ($exts as $ext) {
                $data[$ext] = $ext;
            }

            return $data;
        }
    }

    /**
     * Get a filtered array of allowed extensions for use as the basis for FormField configuration options
     * This is the entry point for getting allowed file extensions
     */
    public function getFilteredAllowedExtensions(): array
    {
        // those that have been selected
        $selectedExtensions = $this->getOwner()->AllowedFileExtensions->getValues();
        if (!is_array($selectedExtensions)) {
            $selectedExtensions = [];
        }

        // the current system setting
        $systemAllowedExtensions = $this->getSystemAllowedFileTypes();
        // the filtered list of allowed extensions are those selected that are also in the system allowed extensions
        if (count($systemAllowedExtensions) == 0) {
            // none allowed
            return [];
        }

        // return all values of $selectedExtensions present in $systemAllowedExtensions
        $filtered = array_intersect($selectedExtensions, $systemAllowedExtensions);
        return $filtered;
    }

    /**
     * Validate input
     */
    #[\Override]
    public function validate(ValidationResult $validationResult)
    {
        $types = $this->getSystemAllowedFileTypes();
        $supplied = $this->getOwner()->AllowedFileExtensions;
        if (is_array($supplied)) {
            $diff = array_diff($supplied, $types);
            if ($diff !== []) {
                $validationResult->addFieldError(
                    'AllowedFileExtensions',
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
     * Update fields
     */
    #[\Override]
    public function updateCMSFields(FieldList $fields)
    {
        $source = $this->getSystemAllowedFileTypes();
        if (count($source) == 0) {
            $description = _t(
                self::class . '.NO_SYSTEM_CONFIGURED_FILE_TYPES',
                'There are no configured file types at the system level.'
            );
        } else {
            $description = _t(
                self::class . '.SYSTEM_CONFIGURED_FILE_TYPES',
                'The list of allowed file types is configured at the system level'
            );
        }

        $fields->addFieldToTab(
            'Root.Uploads',
            MultiValueListField::create(
                'AllowedFileExtensions',
                _t(
                    self::class . '.SELECT_ALLOWED_TYPES',
                    'Select allowed file types for uploads to this website'
                ),
                $source
            )->setDescription($description)
        );
    }
}
