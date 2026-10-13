<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    // Exception domain yang dilempar ketika transisi status cucian melanggar aturan state machine
}
