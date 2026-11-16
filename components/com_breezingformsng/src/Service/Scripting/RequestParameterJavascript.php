<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Scripting;

\defined('_JEXEC') or die;

final class RequestParameterJavascript
{
    /** @param array<array-key, string> $parameters */
    public static function build(array $parameters): string
    {
        $code = 'var ff_param = new Object();';
        $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;

        foreach ($parameters as $name => $value) {
            if (!is_string($name) || !str_starts_with($name, 'ff_param_')) {
                continue;
            }

            $key = json_encode(substr($name, 9), $flags);
            $encodedValue = json_encode($value, $flags);
            $code .= "\nObject.defineProperty(ff_param, $key, {value: $encodedValue, "
                . 'writable: true, enumerable: true, configurable: true});';
        }

        return $code;
    }
}
