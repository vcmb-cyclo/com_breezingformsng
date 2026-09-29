<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingFormsNG\Tests\Site\Callback;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PaymentCallbackRegressionTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    /**
     * @return iterable<string, array{string}>
     */
    public static function callbackProvider(): iterable
    {
        yield 'Stripe' => ['StripeCallback'];
        yield 'PayPal' => ['PayPalCallback'];
        yield 'Sofort' => ['SofortCallback'];
    }

    public function testPaymentServicesUseJoomlaQueryBuilderAndBoundParameters(): void
    {
        foreach (['PaymentFormLoader', 'PaymentRecordService'] as $service) {
            $source = $this->read("components/com_breezingformsng/src/Service/Callback/{$service}.php");

            self::assertStringContainsString('->getQuery(true)', $source, $service);
            self::assertStringContainsString('->quoteName(', $source, $service);
            self::assertStringContainsString('->bind(', $source, $service);
        }
    }

    #[DataProvider('callbackProvider')]
    public function testCallbacksDoNotPassEnglishSentencesAsTranslationKeys(string $callback): void
    {
        $source = $this->read("components/com_breezingformsng/src/Service/Callback/{$callback}.php");

        self::assertDoesNotMatchRegularExpression(
            '/Text::_\\(\\s*([\'"])(?!COM_|J[A-Z_]|DATE_).*?\\1\\s*\\)/',
            $source
        );
    }

    public function testPaymentMessagesExistInEveryPackagedLanguageFile(): void
    {
        $keys = [
            'COM_BREEZINGFORMSNG_PAYMENT_AMOUNT_CURRENCY_INVALID',
            'COM_BREEZINGFORMSNG_PAYMENT_TRANSACTION_ALREADY_PROCESSED',
            'COM_BREEZINGFORMSNG_PAYMENT_RECORD_NOT_FOUND',
            'COM_BREEZINGFORMSNG_PAYMENT_VERIFICATION_FAILED',
            'COM_BREEZINGFORMSNG_PAYMENT_VERIFICATION_EMPTY',
            'COM_BREEZINGFORMSNG_PAYMENT_TRANSACTION_ID_EMPTY',
        ];
        $files = array_merge(
            glob(self::ROOT . '/components/com_breezingformsng/language/*/com_breezingformsng.ini') ?: [],
            glob(self::ROOT . '/administrator/components/com_breezingformsng/language/*/com_breezingformsng.ini') ?: []
        );

        self::assertCount(16, $files);

        foreach ($files as $file) {
            $translations = parse_ini_file($file);

            self::assertIsArray($translations, "Invalid language file {$file}");

            foreach ($keys as $key) {
                self::assertArrayHasKey($key, $translations, "Missing {$key} in {$file}");
                self::assertNotSame('', trim((string) $translations[$key]), "Empty {$key} in {$file}");
            }
        }
    }

    public function testDownloadCallbacksDelegateAttemptLimitToSharedPolicy(): void
    {
        foreach (['StripeCallback', 'PayPalCallback', 'SofortCallback'] as $callback) {
            $source = $this->read("components/com_breezingformsng/src/Service/Callback/{$callback}.php");

            self::assertStringContainsString('PaymentDownloadService $paymentDownloadService', $source, $callback);
            self::assertStringNotContainsString('new PaymentDownloadService(', $source, $callback);
            self::assertStringContainsString('->download(', $source, $callback);
            self::assertStringNotContainsString(
                'paypal_download_tries < $options[\'downloadTries\']',
                $source,
                $callback
            );
        }

        $service = $this->read('components/com_breezingformsng/src/Service/Callback/PaymentDownloadService.php');

        self::assertStringContainsString('$this->downloadPolicy->canDownload(', $service);
    }

    public function testProviderSharesPaymentDownloadPolicyWithPaymentCallbacks(): void
    {
        $source = $this->read('administrator/components/com_breezingformsng/services/provider.php');

        self::assertStringContainsString('PaymentDownloadPolicy::class', $source);
        self::assertStringContainsString('PaymentDownloadService::class', $source);
        self::assertStringContainsString('PaymentRecordService::class', $source);
        self::assertSame(1, substr_count($source, '$container->get(PaymentDownloadPolicy::class)'));
        self::assertSame(3, substr_count($source, '$container->get(PaymentDownloadService::class)'));
        self::assertSame(3, substr_count($source, '$container->get(PaymentRecordService::class)'));

        $dispatcher = $this->read('components/com_breezingformsng/src/Service/EngineDispatcher.php');

        self::assertStringNotContainsString('$this->paymentDownloadPolicy', $dispatcher);
    }

    public function testPaymentFactoriesMatchConstructorDependencies(): void
    {
        $source = $this->read('administrator/components/com_breezingformsng/services/provider.php');

        foreach (['PaymentDownloadService', 'StripeCallback', 'PayPalCallback', 'SofortCallback'] as $service) {
            self::assertSame(1, preg_match('/return new ' . $service . '\\((.*?)\\n\\s*\\);/s', $source, $match));
            preg_match_all('/\\$container->get\\((\\w+)::class\\)/', $match[1], $dependencies);
            $constructor = new \ReflectionMethod(
                'Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Callback\\' . $service,
                '__construct'
            );
            $expected = [];
            foreach ($constructor->getParameters() as $parameter) {
                if (in_array($parameter->getName(), ['application', 'http'], true)) {
                    continue;
                }
                $type = $parameter->getType()->getName();
                $expected[] = substr($type, strrpos($type, '\\') + 1);
            }
            self::assertSame($expected, $dependencies[1], $service);
        }
    }

    public function testPaymentCallbacksDelegateFormLoadingToTheSharedLoader(): void
    {
        foreach (['StripeCallback', 'PayPalCallback', 'SofortCallback'] as $callback) {
            $source = $this->read("components/com_breezingformsng/src/Service/Callback/{$callback}.php");

            self::assertStringContainsString('PaymentFormLoader $paymentFormLoader', $source, $callback);
            self::assertStringContainsString('$this->paymentFormLoader->load(', $source, $callback);
            self::assertStringContainsString('$this->paymentFormLoader->decodeAreas(', $source, $callback);
            self::assertStringNotContainsString(
                "->from(\$db->quoteName('#__facileforms_forms'))",
                $source,
                $callback
            );
            self::assertStringNotContainsString(
                '->update($db->quoteName(\'#__facileforms_records\'))',
                $source,
                $callback
            );
        }
    }

    public function testStripeSubmissionIteratesEveryTemplateArea(): void
    {
        $source = $this->read('components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php');
        $stripeStart = strrpos($source, "case 'Stripe':");
        $paypalStart = strpos($source, "case 'PayPal':", $stripeStart);

        self::assertIsInt($stripeStart);
        self::assertIsInt($paypalStart);
        self::assertStringContainsString(
            'foreach ($areas as $area)',
            substr($source, $stripeStart, $paypalStart - $stripeStart)
        );
        self::assertStringNotContainsString('isset($stripeemail)', $source);
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertNotFalse($source, "Unable to read {$path}");

        return $source;
    }
}
