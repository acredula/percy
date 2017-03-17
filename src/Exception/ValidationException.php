<?php

namespace Percy\Exception;

use DomainException;

class ValidationException extends DomainException
{
    /**
     * Set failures.
     *
     * @param array
     */
    public function setFailures($failures)
    {
        $this->failures = $failures;
    }

    /**
     * Get failures.
     *
     * @return array
     */
    public function getFailures()
    {
        return $this->failures;
    }
}
