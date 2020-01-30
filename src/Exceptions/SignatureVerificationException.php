<?php

namespace BinaryCats\SurveyMonkeyWebhooks\Exceptions;

use Exception;

class SignatureVerificationException extends Exception
{
    public function render($request)
    {
        return response(['error' => $this->getMessage()], $this->getCode());
    }
}
