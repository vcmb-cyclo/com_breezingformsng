<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

require_once __DIR__ . '/../Rendering/QuickMode/joomla-cmsapplication-stub.php';
require_once __DIR__ . '/../Rendering/QuickMode/joomla-text-stub.php';
require_once __DIR__ . '/../Rendering/QuickMode/joomla-uri-stub.php';

use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadFilePolicy;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadPolicy;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadService;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentFormLoader;
use Vcmb\Component\BreezingformsNG\Site\Service\Support\RedirectHelper;

if (!interface_exists(DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

if (!class_exists(ParameterType::class)) {
    eval('namespace Joomla\\Database; final class ParameterType { public const INTEGER = 1; public const STRING = 2; }');
}

final class PaymentDownloadServiceTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../../..';

    protected function setUp(): void
    {
        defined('JPATH_SITE') || define('JPATH_SITE', self::ROOT);
    }

    public function testRedirectsWhenTheFormDoesNotExist(): void
    {
        $application = new PaymentDownloadApplicationDouble();
        $database = new PaymentDownloadDatabaseDouble([]);

        $this->service($application, $database)->download(
            'bfStripe',
            'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYMENT_DATA',
            'token',
            'Stripe'
        );

        self::assertSame(
            ['COM_BREEZINGFORMSNG_FORM_DOES_NOT_EXIST'],
            $application->messages
        );
        self::assertTrue($application->closed);
    }

    public function testRejectsAnUnknownTransactionWithoutUpdatingTheRecord(): void
    {
        $application = $this->application(['form' => 4, 'record_id' => 12, 'token' => 'abc']);
        $database = new PaymentDownloadDatabaseDouble([
            [$this->form('bfStripe')],
            [],
        ]);

        $this->service($application, $database)->download(
            'bfStripe',
            'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYMENT_DATA',
            'token',
            'Stripe'
        );

        self::assertSame(['COM_BREEZINGFORMSNG_DOWNLOAD_NOT_POSSIBLE'], $application->messages);
        self::assertSame(2, count($database->queries));
        self::assertSame(0, $database->executions);
    }

    public function testSupportsPaypalPlainAndValidatedTransactionsAndEnforcesTheQuota(): void
    {
        $application = $this->application(['form' => 4, 'record_id' => 12, 'tx' => 'abc']);
        $database = new PaymentDownloadDatabaseDouble([
            [$this->form('bfPayPal')],
            [(object) ['paypal_download_tries' => 3]],
        ]);

        $this->service($application, $database)->download(
            'bfPayPal',
            'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYPAL_DATA',
            'tx',
            'PayPal',
            true
        );

        self::assertSame(['COM_BREEZINGFORMSNG_MAX_DOWNLOAD_TRIES_REACHED'], $application->messages);
        self::assertSame(2, count($database->queries));
        self::assertSame(
            [
                ':downloadRecordId' => '12',
                ':downloadFormId' => '4',
                ':paymentTransaction0' => 'PayPal: abc',
                ':paymentTransaction1' => 'PayPal: abc (VALID)',
            ],
            $database->queries[1]->bindings
        );
        self::assertSame(0, $database->executions);
    }

    public function testSkipsUnrelatedPaymentElements(): void
    {
        $application = $this->application(['form' => 4]);
        $database = new PaymentDownloadDatabaseDouble([[$this->form('bfPayPal')]]);

        $this->service($application, $database)->download(
            'bfStripe',
            'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYMENT_DATA',
            'token',
            'Stripe'
        );

        self::assertSame([], $application->messages);
        self::assertSame(1, count($database->queries));
    }

    public function testMatchesTheRecordFormAndClaimsTheDownloadWithOneConditionalUpdate(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'bfng');
        rename($file, $file . '.zip');
        $file .= '.zip';
        file_put_contents($file, 'x');
        try {
            $application = $this->application(['form' => 4, 'record_id' => 12, 'token' => 'abc']);
            $database = new PaymentDownloadDatabaseDouble([
                [$this->form('bfStripe', $file)],
                [(object) ['paypal_download_tries' => 1]],
            ]);

            try {
                $this->service($application, $database)->download(
                    'bfStripe',
                    'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYMENT_DATA',
                    'token',
                    'Stripe'
                );
            } catch (\Throwable) {
                // Streaming the file is outside the scope of this test.
            }

            self::assertSame(1, $database->executions);
            foreach ([1, 2] as $index) {
                self::assertSame('4', $database->queries[$index]->bindings[':downloadFormId']);
                self::assertSame('12', $database->queries[$index]->bindings[':downloadRecordId']);
            }
            self::assertSame('3', $database->queries[2]->bindings[':maximumTries']);
            self::assertTrue($database->queries[2]->conditionsInclude('< :maximumTries'));
            self::assertTrue($database->queries[1]->conditionsInclude('form'));
        } finally {
            unlink($file);
        }
    }

    public function testRefusesWhenAConcurrentRequestAlreadyConsumedTheLastDownload(): void
    {
        $application = $this->application(['form' => 4, 'record_id' => 12, 'token' => 'abc']);
        $database = new PaymentDownloadDatabaseDouble([
            [$this->form('bfStripe')],
            [(object) ['paypal_download_tries' => 2]],
        ]);
        $database->affectedRows = 0;

        $this->service($application, $database)->download(
            'bfStripe',
            'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYMENT_DATA',
            'token',
            'Stripe'
        );

        self::assertSame(['COM_BREEZINGFORMSNG_MAX_DOWNLOAD_TRIES_REACHED'], $application->messages);
    }

    public function testAFileUnderASensitiveRootIsRefusedAndNotStreamed(): void
    {
        $directory = sys_get_temp_dir() . '/bfng-sensitive-' . bin2hex(random_bytes(4));
        mkdir($directory);
        $file = $directory . '/backup.zip';
        file_put_contents($file, 'x');

        try {
            $application = $this->application(['form' => 4, 'record_id' => 12, 'token' => 'abc']);
            $database = new PaymentDownloadDatabaseDouble([
                [$this->form('bfStripe', $file)],
                [(object) ['paypal_download_tries' => 1]],
            ]);

            $this->service($application, $database, new PaymentDownloadFilePolicy([$directory]))->download(
                'bfStripe',
                'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYMENT_DATA',
                'token',
                'Stripe'
            );

            self::assertSame(['COM_BREEZINGFORMSNG_COULD_NOT_FIND_DOWNLOAD_FILE'], $application->messages);
        } finally {
            unlink($file);
            rmdir($directory);
        }
    }

    private function service(
        PaymentDownloadApplicationDouble $application,
        PaymentDownloadDatabaseDouble $database,
        ?PaymentDownloadFilePolicy $filePolicy = null
    ): PaymentDownloadService {
        return new PaymentDownloadService(
            $application,
            $database,
            new PaymentFormLoader($database),
            new RedirectHelper($application),
            new PaymentDownloadPolicy(),
            $filePolicy ?? new PaymentDownloadFilePolicy()
        );
    }

    /** @param array<string, mixed> $values */
    private function application(array $values): PaymentDownloadApplicationDouble
    {
        $application = new PaymentDownloadApplicationDouble();
        $application->getInput()->values = $values;

        return $application;
    }

    private function form(string $internalType, string $filepath = '/tmp/bfng-download-does-not-exist'): object
    {
        return (object) [
            'template_areas' => json_encode([
                [
                    'elements' => [
                        [
                            'internalType' => $internalType,
                            'options' => [
                                'downloadableFile' => true,
                                'downloadTries' => 3,
                                'filepath' => $filepath,
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ];
    }
}

final class PaymentDownloadApplicationDouble extends CMSApplication
{
    /** @var list<string> */
    public array $messages = [];

    public bool $closed = false;

    public function enqueueMessage(string $message, string $type = 'message'): void
    {
        $this->messages[] = $message;
    }

    public function redirect(string $url, int $status = 303, bool $moved = true): void
    {
    }

    public function close(): void
    {
        $this->closed = true;
    }
}

final class PaymentDownloadDatabaseDouble implements DatabaseInterface
{
    /** @var list<PaymentDownloadQueryDouble> */
    public array $queries = [];

    public int $executions = 0;

    public int $affectedRows = 1;

    /** @var list<list<object>> */
    private array $results;

    /** @param list<list<object>> $results */
    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function createQuery(): PaymentDownloadQueryDouble
    {
        return $this->queries[] = new PaymentDownloadQueryDouble();
    }

    public function quoteName(string|array $name): string|array
    {
        return $name;
    }

    public function setQuery(object $query, int $offset = 0, int $limit = 0): void
    {
    }

    /** @return list<object> */
    public function loadObjectList(): array
    {
        return array_shift($this->results) ?? [];
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    public function execute(): bool
    {
        $this->executions++;

        return true;
    }
}

final class PaymentDownloadQueryDouble
{
    /** @var array<string, string> */
    public array $bindings = [];

    public function select(mixed $columns): self
    {
        return $this;
    }

    public function from(string $table): self
    {
        return $this;
    }

    /** @var list<string> */
    public array $conditions = [];

    public function conditionsInclude(string $needle): bool
    {
        return str_contains(implode(' ', $this->conditions), $needle);
    }

    public function where(string $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function extendWhere(string $outerGlue, array $conditions, string $innerGlue): self
    {
        return $this;
    }

    public function bind(string $key, mixed $value, mixed $type): self
    {
        $this->bindings[$key] = (string) $value;

        return $this;
    }

    public function update(string $table): self
    {
        return $this;
    }

    public function set(string $condition): self
    {
        return $this;
    }
}
