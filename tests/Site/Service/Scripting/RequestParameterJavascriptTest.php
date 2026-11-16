<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Scripting;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Scripting\RequestParameterJavascript;

final class RequestParameterJavascriptTest extends TestCase
{
    public function testParametersAreEncodedForAnInlineScript(): void
    {
        $value = "O'Reilly </script><script>alert(1)</script>\n";
        $script = RequestParameterJavascript::build([
            'ff_param_name' => $value,
            'ff_param_bad\'];alert(1)//' => 'value',
            'ff_param___proto__' => 'safe',
            'unrelated' => 'ignored',
        ]);

        self::assertStringNotContainsString('</script', $script);
        self::assertStringNotContainsString("O'Reilly", $script);
        self::assertStringNotContainsString('ff_param_bad', $script);
        self::assertStringNotContainsString('ignored', $script);
        self::assertStringContainsString('\\u003C/script\\u003E', $script);
        self::assertStringContainsString('Object.defineProperty(ff_param, "__proto__"', $script);
        self::assertStringContainsString('Object.defineProperty(ff_param, "name"', $script);
        $pattern = '/Object\\.defineProperty\\(ff_param, "name", \\{value: (.*?), writable:/';
        self::assertSame(1, preg_match($pattern, $script, $match));
        self::assertSame($value, json_decode($match[1], true, 512, JSON_THROW_ON_ERROR));
    }
}
