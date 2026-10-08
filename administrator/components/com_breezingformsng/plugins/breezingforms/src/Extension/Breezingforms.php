<?php

declare(strict_types=1);

namespace Vcmb\Plugin\Content\Breezingforms\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use RuntimeException;

final class Breezingforms extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepare' => 'prepareContent'];
    }

    public function prepareContent(ContentPrepareEvent $event): void
    {
        $item = $event->getItem();
        $app = $this->getApplication();
        if (
            !$app instanceof CMSApplication
            || !$app->isClient('site')
            || $event->getContext() === 'com_content.category'
            || !isset($item->text)
            || !is_file(JPATH_SITE . '/components/com_breezingformsng/breezingformsng.php')
        ) {
            return;
        }

        $pattern = '/\{\s*BreezingForms\s*:\s*([A-Za-z0-9_-]+)'
            . '(?:\s*,\s*(\d*)(?:\s*,\s*([01]?)(?:\s*,\s*([^},]*)'
            . '(?:\s*,\s*([^\s},]*)(?:\s*,\s*([01]?)(?:\s*,\s*([01]?))?)?)?)?)?)?\s*\}/s';
        $item->text = preg_replace_callback(
            $pattern,
            fn (array $matches): string => $this->renderForm($matches, (int) ($item->id ?? 0), $app),
            $item->text
        );
    }

    private function renderForm(array $matches, int $contentId, CMSApplication $app): string
    {
        $name = $matches[1];
        $database = $this->getDatabase();
        $query = $database->getQuery(true)
            ->select('*')
            ->from($database->quoteName('#__facileforms_forms'))
            ->where($database->quoteName('name') . ' = :name')
            ->where($database->quoteName('published') . ' = 1')
            ->where($database->quoteName('runmode') . ' < 2')
            ->order([$database->quoteName('ordering'), $database->quoteName('id')])
            ->bind(':name', $name);
        $form = $database->setQuery($query, 0, 1)->loadObject();
        if ($form === null) {
            return htmlspecialchars(Text::sprintf('PLG_CONTENT_BREEZINGFORMS_NOT_FOUND', $name), ENT_QUOTES, 'UTF-8');
        }

        $input = $app->getInput();
        $original = [];
        foreach (array_keys($input->getArray()) as $key) {
            $original[$key] = $input->get($key, null, 'raw');
        }
        require_once JPATH_SITE . '/components/com_breezingformsng/src/Support/runtime_bootstrap.php';
        $target = (int) $GLOBALS['ff_target'] + 1;
        $isTarget = $input->getInt('ff_target', 1) === $target;
        $values = [
            'ff_form' => (int) $form->id,
            'ff_name' => $name,
            'ff_contentid' => $contentId,
            'ff_applic' => 'plg_facileforms',
            'ff_page' => max(1, (int) (($matches[2] ?? '') !== '' ? $matches[2] : 1)),
            'ff_border' => (int) (($matches[3] ?? '') !== '' ? $matches[3] : 1),
            'ff_suffix' => $matches[5] ?? '',
            'ff_frame' => 0,
            'ff_runmode' => 0,
            'ff_target' => $target,
            'raw' => false,
            'ff_task' => $isTarget ? $input->getCmd('ff_task', 'view') : 'view',
            'ff_form_submitted' => $isTarget ? $input->getInt('ff_form_submitted', 0) : 0,
            'ff_status' => $isTarget ? $input->getCmd('ff_status', '') : '',
            'ff_message' => $isTarget ? $input->getString('ff_message', '') : '',
        ];
        parse_str(html_entity_decode($matches[4] ?? '', ENT_QUOTES, 'UTF-8'), $parameters);
        foreach ($parameters as $key => $value) {
            if (str_starts_with($key, 'ff_param_') && is_scalar($value)) {
                $values[$key] = (string) $value;
            }
        }
        if ($isTarget) {
            foreach ($original as $key => $value) {
                if (str_starts_with($key, 'ff_param_') && is_scalar($value)) {
                    $values[$key] = (string) $value;
                }
            }
            $values['ff_page'] = $input->getInt('ff_page', $values['ff_page']);
        }
        $plg_editable = (int) ($matches[6] ?? 0);
        $plg_editable_override = (int) ($matches[7] ?? 0);
        $app->getSession()->set('ff_editablePlg' . $contentId . $name, $plg_editable);
        $app->getSession()->set('ff_editable_overridePlg' . $contentId . $name, $plg_editable_override);

        if ($this->params->get('load_in_iframe', 0)) {
            $GLOBALS['ff_target'] = $target;
            if ((int) $form->autoheight === 1) {
                $app->getDocument()->getWebAssetManager()->registerAndUseScript(
                    'plg_content.breezingforms.resize',
                    Uri::root(true) . '/plugins/content/breezingforms/js/resize.js',
                    [],
                    ['defer' => true]
                );
            }
            $values['ff_frame'] = 1;
            $values['ff_task'] = 'view';
            $values['option'] = 'com_breezingformsng';
            $values['tmpl'] = 'component';
            $values['Itemid'] = $input->getInt('Itemid', 0);
            $url = Uri::root() . 'index.php?' . http_build_query($values);
            $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            return '<iframe class="breezingforms_iframe_plg" data-autoheight="' . (int) $form->autoheight
                . '" title="' . $escape((string) $form->title)
                . '" src="' . $escape($url) . '" width="'
                . $escape((string) $form->width . ($form->widthmode ? '%' : ''))
                . '" height="' . (int) $form->height . '" style="border:' . ($values['ff_border'] ? '1px solid' : '0')
                . '" sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals'
                . ' allow-top-navigation"></iframe>';
        }

        if ($values['ff_task'] === 'submit' && !Session::checkToken('post')) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }
        // The runtime reads Joomla input; restore the article request even if rendering fails.
        foreach (array_keys($original) as $key) {
            if (str_starts_with($key, 'ff_param_')) {
                $input->set($key, null);
            }
        }
        foreach ($values as $key => $value) {
            $input->set($key, $value);
        }
        $ff_applic = 'plg_facileforms';
        $level = ob_get_level();
        ob_start();
        try {
            require JPATH_SITE . '/components/com_breezingformsng/breezingformsng.php';
            return (string) ob_get_contents();
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            $restoreKeys = array_unique([
                ...array_keys($values),
                ...array_filter(
                    array_keys($original),
                    static fn (string $key): bool => str_starts_with($key, 'ff_param_')
                ),
            ]);
            foreach ($restoreKeys as $key) {
                $input->set($key, $original[$key] ?? null);
            }
        }
    }
}
