<?php

namespace NSWDPC\FileTypeManagement\Models;

use SilverStripe\Core\Config\Configurable;

class Configuration {

    use Configurable;

    private static array $allowed_extensions_denylist = [];

}
