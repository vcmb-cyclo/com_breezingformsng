<?php

declare(strict_types=1);

namespace Vcmb\Module\Breezingforms\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use RuntimeException;

final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $data['form'] = $this->renderForm($data['params']);

        return $data;
    }

    private function renderForm(Registry $params): string
    {
        if (!ComponentHelper::isEnabled('com_breezingformsng')) {
            return htmlspecialchars(Text::_('MOD_BREEZINGFORMS_COMPONENT_REQUIRED'), ENT_QUOTES, 'UTF-8');
        }

        $name = trim((string) $params->get('ff_mod_name', ''));
        $database = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $database->getQuery(true)
            ->select($database->quoteName('id'))
            ->from($database->quoteName('#__facileforms_forms'))
            ->where($database->quoteName('name') . ' = :name')
            ->where($database->quoteName('published') . ' = 1')
            ->where($database->quoteName('runmode') . ' < 2')
            ->order([$database->quoteName('ordering'), $database->quoteName('id')])
            ->bind(':name', $name);
        $formId = (int) $database->setQuery($query, 0, 1)->loadResult();
        if ($formId === 0) {
            return htmlspecialchars(Text::sprintf('MOD_BREEZINGFORMS_NOT_FOUND', $name), ENT_QUOTES, 'UTF-8');
        }

        require_once JPATH_SITE . '/components/com_breezingformsng/src/Support/runtime_bootstrap.php';
        $input = $this->getApplication()->getInput();
        $target = (int) $GLOBALS['ff_target'] + 1;
        $isTarget = $input->getInt('ff_target', 1) === $target;
        $values = [
            // Resolve by name so the engine also applies private module parameters.
            'ff_form' => null,
            'ff_name' => $name,
            'ff_applic' => 'mod_facileforms',
            'ff_module_id' => (int) $this->module->id,
            'ff_runmode' => 0,
            'ff_task' => $isTarget ? $input->getCmd('ff_task', 'view') : 'view',
            'ff_page' => $isTarget ? $input->getInt('ff_page', max(1, (int) $params->get('ff_mod_page', 1)))
                : max(1, (int) $params->get('ff_mod_page', 1)),
            'ff_frame' => (int) $params->get('ff_mod_frame', 0),
            'ff_border' => (int) $params->get('ff_mod_border', 0),
            'ff_align' => (int) $params->get('ff_mod_align', 1),
            'ff_top' => (int) $params->get('ff_mod_top', 0),
            'ff_suffix' => (string) $params->get('ff_mod_suffix', ''),
            'raw' => false,
        ];
        // Keep the configured pixel offset when the engine reads request alignment.
        if ($values['ff_align'] === 3) {
            $values['ff_align'] = -1;
        }
        if ($values['ff_task'] === 'submit' && !Session::checkToken('post')) {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $original = [];
        foreach (array_keys($input->getArray()) as $key) {
            $original[$key] = $input->get($key, null, 'raw');
            if (!$isTarget && str_starts_with($key, 'ff_param_')) {
                $values[$key] = null;
            }
        }
        foreach ($values as $key => $value) {
            $input->set($key, $value);
        }
        $ff_applic = 'mod_facileforms';
        $xModuleId = (int) $this->module->id;
        $level = ob_get_level();
        ob_start();
        try {
            require JPATH_SITE . '/components/com_breezingformsng/breezingformsng.php';

            return (string) ob_get_contents();
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            foreach ($values as $key => $value) {
                $input->set($key, $original[$key] ?? null);
            }
        }
    }
}
