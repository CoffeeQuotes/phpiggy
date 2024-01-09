<?php

namespace Framework\Rules;

use Framework\Contracts\RuleInterface;
use InvalidArgumentException;

class LengthMaxRule implements RuleInterface
{
    public function validate(array $data, string $field, array $params): bool
    {
        if (empty($params[0])) {
            throw new InvalidArgumentException('Maximum length not specified.');
        }
        $length = (int) $params[0];
        return strlen($data[$field]) <= $length;
    }

    public function getMessage(array $data, string $field, array $params): string
    {
        return "The field $field must be less than or equal to $params[0] characters.";
    }
}
