<?php

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Joomla\CMS\Log {
    final class Log
    {
        public const WARNING = 4;
        public static function add(string $message, int $priority, string $category): void
        {
        }
    }
}

namespace Joomla\CMS\Application {
    final class CMSApplication
    {
        public function __construct(private object $input)
        {
        }
        public function getInput(): object
        {
            return $this->input;
        }
        public function setHeader(string $name, string $value, bool $replace): void
        {
        }
        public function get(string $key): string
        {
            return 'synthetic-secret';
        }
    }
}

namespace Joomla\Http {
    final class Http
    {
        public string $result = 'INVALID';
        public function post(string $url, string $body, array $headers): object
        {
            return new class ($this->result) {
                public function __construct(private string $body)
                {
                }
                public function getBody(): string
                {
                    return $this->body;
                }
            };
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Site\Service\Submission {
    final class PayPalPayment
    {
        public function verify(string $custom, int $recordId, string $secret): ?array
        {
            return $custom === 'valid' ? ['amount' => '10'] : null;
        }
        public function matches(array $expected, string $amount, string $currency): bool
        {
            return $amount === '10' && $currency === 'EUR';
        }
        public function isReceiver(string $business, string $email, string $id): bool
        {
            return $business !== '' && $business === $email;
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Site\Service\Support {
    final class RedirectHelper
    {
    }
}

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback {
    final class PaymentFormLoader
    {
        public function load(int $id): object
        {
            return (object) ['id' => $id];
        }
        public function decodeAreas(object $form): array
        {
            return [['elements' => [[
                'internalType' => 'bfPayPal',
                'options' => ['token' => '', 'business' => 'shop@example.com', 'testaccount' => false, 'sendNotificationAfterPayment' => false],
            ]]]];
        }
    }
    final class PaymentRecordService
    {
        public int $writes = 0;
        public object $record;
        public function __construct()
        {
            $this->record = (object) ['paypal_tx_id' => ''];
        }
        public function find(int $recordId): object
        {
            return $this->record;
        }
        public function storeTransaction(int $recordId, string $transaction, string $date, int $test): void
        {
            $this->writes++;
            $this->record->paypal_tx_id = $transaction;
        }
    }
    final class PaymentDownloadService
    {
    }
}

namespace {
    define('_JEXEC', 1);
    define('JPATH_SITE', sys_get_temp_dir() . '/bf-no-paypal-hook-' . bin2hex(random_bytes(8)));
    final class SyntheticInput
    {
        public object $post;
        public function __construct()
        {
            $this->post = $this;
        }
        public function getInt(string $name, int $default): int
        {
            return match ($name) {
                'form_id' => 1,
                'record_id' => 42,
                default => $default,
            };
        }
        public function getString(string $name, string $default): string
        {
            return match ($name) {
                'txn_id' => 'synthetic', 'custom' => 'valid', 'payment_status' => 'Completed',
                'mc_gross' => '10', 'mc_currency' => 'EUR',
                'receiver_email' => (string) (getenv('IPN_RECEIVER') ?: 'shop@example.com'), default => $default,
            };
        }
        public function getArray(): array
        {
            return ['custom' => 'valid'];
        }
    }

    require dirname(__DIR__, 5) . '/components/com_breezingformsng/src/Service/Callback/PayPalCallback.php';
    $records = new \Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentRecordService();
    $http = new \Joomla\Http\Http();
    $callback = new \Vcmb\Component\BreezingformsNG\Site\Service\Callback\PayPalCallback(
        new \Joomla\CMS\Application\CMSApplication(new SyntheticInput()),
        new \Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentFormLoader(),
        $records,
        new \Vcmb\Component\BreezingformsNG\Site\Service\Support\RedirectHelper(),
        new \Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadService(),
        $http
    );
    $callback->confirmIpn();
    $afterInvalid = [$records->writes, $records->record->paypal_tx_id];
    $http->result = 'VERIFIED';
    $callback->confirmIpn();
    $callback->confirmIpn();
    echo json_encode([$afterInvalid, $records->writes, $records->record->paypal_tx_id], JSON_THROW_ON_ERROR);
}
