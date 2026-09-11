<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

// Joomla loads this provider while installing the component, before its
// extension namespaces are registered with the autoloader. On a normal
// request the namespaces resolve already, so only register the fallback
// paths when the component's own classes cannot be autoloaded yet.
if (!class_exists('Vcmb\\Component\\BreezingformsNG\\Administrator\\Extension\\BreezingFormsNGComponent')) {
    $packageRoot = dirname(__DIR__, 4);
    \JLoader::registerNamespace(
        'Vcmb\\Component\\BreezingformsNG\\Administrator',
        dirname(__DIR__) . '/src',
    );
    \JLoader::registerNamespace(
        'Vcmb\\Component\\BreezingformsNG\\Site',
        $packageRoot . '/components/com_breezingformsng/src',
    );
}

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Http\HttpFactory;
use Vcmb\Component\BreezingformsNG\Administrator\Extension\BreezingFormsNGComponent;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\CaptchaCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\FlashUploadCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\OptCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PayPalCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadPolicy;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentDownloadService;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentFormLoader;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentRecordService;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\SofortCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\StripeCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\EngineDispatcher;
use Vcmb\Component\BreezingformsNG\Site\Service\FormRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RequestParameterParser;
use Vcmb\Component\BreezingformsNG\Site\Service\Support\RedirectHelper;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashUploadSizeValidator;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadFileCleaner;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $namespace = 'Vcmb\\Component\\BreezingformsNG';
        $application = Factory::getApplication();

        $container->registerServiceProvider(new MVCFactory($namespace));
        $container->registerServiceProvider(new ComponentDispatcherFactory($namespace));
        $container->registerServiceProvider(new RouterFactory($namespace));

        $container->set(
            RequestParameterParser::class,
            static fn(): RequestParameterParser => new RequestParameterParser(),
        );

        $container->share(
            PaymentDownloadPolicy::class,
            static fn(): PaymentDownloadPolicy => new PaymentDownloadPolicy(),
        );

        $container->share(
            PaymentRecordService::class,
            static function (Container $container): PaymentRecordService {
                return new PaymentRecordService($container->get(DatabaseInterface::class));
            },
        );

        $container->share(
            PaymentFormLoader::class,
            static function (Container $container): PaymentFormLoader {
                return new PaymentFormLoader($container->get(DatabaseInterface::class));
            },
        );

        $container->share(
            PaymentDownloadService::class,
            static function (Container $container) use ($application): PaymentDownloadService {
                return new PaymentDownloadService(
                    $application,
                    $container->get(DatabaseInterface::class),
                    $container->get(PaymentFormLoader::class),
                    $container->get(PaymentRecordService::class),
                    $container->get(RedirectHelper::class),
                    $container->get(PaymentDownloadPolicy::class),
                );
            },
        );

        $container->share(
            RedirectHelper::class,
            static function () use ($application): RedirectHelper {
                return new RedirectHelper($application);
            },
        );

        $container->set(
            FlashUploadSizeValidator::class,
            static fn(): FlashUploadSizeValidator => new FlashUploadSizeValidator(),
        );

        $container->set(
            UploadFileCleaner::class,
            static fn(): UploadFileCleaner => new UploadFileCleaner(),
        );

        $container->set(
            FormRenderer::class,
            static function (Container $container) use ($application): FormRenderer {
                return new FormRenderer(
                    $application,
                    $container->get(DatabaseInterface::class),
                    $container->get(MailerFactoryInterface::class),
                    $container->get(CacheControllerFactoryInterface::class),
                    $container->get(RequestParameterParser::class),
                    $container->get(UploadFileCleaner::class),
                );
            }
        );

        $container->set(
            CaptchaCallback::class,
            static function () use ($application): CaptchaCallback {
                return new CaptchaCallback($application);
            },
        );

        $container->set(
            PayPalCallback::class,
            static function (Container $container) use ($application): PayPalCallback {
                return new PayPalCallback(
                    $application,
                    $container->get(DatabaseInterface::class),
                    $container->get(PaymentFormLoader::class),
                    $container->get(PaymentRecordService::class),
                    $container->get(RedirectHelper::class),
                    $container->get(PaymentDownloadService::class),
                    (new HttpFactory())->getHttp(),
                );
            }
        );

        $container->set(
            StripeCallback::class,
            static function (Container $container) use ($application): StripeCallback {
                return new StripeCallback(
                    $application,
                    $container->get(DatabaseInterface::class),
                    $container->get(PaymentFormLoader::class),
                    $container->get(PaymentRecordService::class),
                    $container->get(RedirectHelper::class),
                    $container->get(PaymentDownloadService::class),
                );
            }
        );

        $container->set(
            SofortCallback::class,
            static function (Container $container) use ($application): SofortCallback {
                return new SofortCallback(
                    $application,
                    $container->get(DatabaseInterface::class),
                    $container->get(PaymentFormLoader::class),
                    $container->get(RedirectHelper::class),
                    $container->get(MailerFactoryInterface::class),
                    $container->get(PaymentDownloadService::class),
                );
            }
        );

        $container->set(
            FlashUploadCallback::class,
            static function (Container $container) use ($application): FlashUploadCallback {
                return new FlashUploadCallback(
                    $application,
                    $container->get(DatabaseInterface::class),
                    $container->get(FlashUploadSizeValidator::class),
                );
            }
        );

        $container->set(
            OptCallback::class,
            static function (Container $container) use ($application): OptCallback {
                return new OptCallback(
                    $application,
                    $container->get(DatabaseInterface::class),
                );
            }
        );

        $container->set(
            EngineDispatcher::class,
            static function (Container $container) use ($application): EngineDispatcher {
                return new EngineDispatcher(
                    $application->getInput(),
                    static function () use ($container): FormRenderer {
                        return $container->get(FormRenderer::class);
                    },
                    static function () use ($container): CaptchaCallback {
                        return $container->get(CaptchaCallback::class);
                    },
                    static function () use ($container): PayPalCallback {
                        return $container->get(PayPalCallback::class);
                    },
                    static function () use ($container): StripeCallback {
                        return $container->get(StripeCallback::class);
                    },
                    static function () use ($container): SofortCallback {
                        return $container->get(SofortCallback::class);
                    },
                    static function () use ($container): FlashUploadCallback {
                        return $container->get(FlashUploadCallback::class);
                    },
                    static function () use ($container): OptCallback {
                        return $container->get(OptCallback::class);
                    },
                );
            }
        );

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new BreezingFormsNGComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );

                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
