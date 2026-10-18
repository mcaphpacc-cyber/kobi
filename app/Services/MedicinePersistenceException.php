<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** Safe application outcome; infrastructure details never enter its message. */
class MedicinePersistenceException extends RuntimeException
{
}
