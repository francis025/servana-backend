<?php

namespace App\Libraries;

use IonAuth\Libraries\IonAuth;

/**
 * IonAuth Wrapper class that allows dynamic properties for PHP 8.2+ compatibility
 * This fixes the "Creation of dynamic property is deprecated" error
 */
#[\AllowDynamicProperties]
class IonAuthWrapper extends IonAuth
{
    // This class inherits all functionality from IonAuth
    // The #[AllowDynamicProperties] attribute allows the parent class
    // to create dynamic properties without triggering deprecation warnings
}
