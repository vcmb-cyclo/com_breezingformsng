<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Finds the inconsistencies of a form and proposes validations, for the MCP
 * tools. It reads the elements and the library scripts only, and returns no
 * code: whether a custom script is empty, not its text.
 *
 * analyse() holds the checks and needs no database.
 */
final class FormChecker
{
    /** Issues returned at most: a broken form could list one per element. */
    public const MAX_ISSUES = 200;

    /** Element types holding a submitted value: they need a name, and a label but the hidden one. */
    private const INPUT_TYPES = [
        'Text', 'Textarea', 'Select List', 'Radio Group', 'Radio Button', 'Checkbox', 'Checkbox Group',
        'File Upload', 'Calendar', 'Hidden Input',
    ];

    /** Script slot of an element, with the library type its script must have. */
    private const SCRIPT_SLOTS = [
        1 => ['init', 'Element Init'],
        2 => ['action', 'Element Action'],
        3 => ['validation', 'Element Validation'],
    ];

    /**
     * Library validations proposed for a text field without validation, by
     * what its name or label says (English, French, German).
     */
    private const SUGGESTIONS = [
        'ff_validemail' => '/(?<![a-z])(e[-_ ]?mail|courriel|mail)(?![a-z])/',
        'ff_url_validation' => '/(?<![a-z])(url|website|site[-_ ]?web|homepage|webseite)(?![a-z])/',
        'ff_validate_date' => '/(?<![a-z])(date|birthday|naissance|geburtsdatum|datum)(?![a-z])/',
        'ff_integer' => '/(?<![a-z])(age|qty|quantity|quantite|anzahl)(?![a-z])/',
        'ff_realamount' => '/(?<![a-z])(amount|price|prix|montant|betrag|preis)(?![a-z])/',
    ];

    public function __construct(private readonly DatabaseInterface $db, private readonly FormCatalog $catalog)
    {
    }

    /**
     * @return array<string, mixed>|null The report, or null when there is no form of this id or name.
     */
    public function check(?int $id, ?string $name): ?array
    {
        $form = $this->catalog->getForm($id, $name, false);
        if ($form === null) {
            return null;
        }

        $db = $this->db;
        $formId = (int) $form['id'];
        $query = $db->createQuery()
            ->select($db->quoteName([
                'id', 'name', 'title', 'type', 'page', 'published',
                'script1cond', 'script1id', 'script2cond', 'script2id', 'script3cond', 'script3id',
            ]))
            ->select([
                // Whether a custom script has text, never the text itself.
                '(CASE WHEN TRIM(' . $db->quoteName('script1code') . ") <> '' THEN 1 ELSE 0 END) AS " . $db->quoteName('script1custom'),
                '(CASE WHEN TRIM(' . $db->quoteName('script2code') . ") <> '' THEN 1 ELSE 0 END) AS " . $db->quoteName('script2custom'),
                '(CASE WHEN TRIM(' . $db->quoteName('script3code') . ") <> '' THEN 1 ELSE 0 END) AS " . $db->quoteName('script3custom'),
            ])
            ->from($db->quoteName('#__facileforms_elements'))
            ->where($db->quoteName('form') . ' = :form')
            ->order([$db->quoteName('page'), $db->quoteName('ordering'), $db->quoteName('id')])
            ->bind(':form', $formId, ParameterType::INTEGER);
        $elements = $db->setQuery($query)->loadAssocList() ?: [];

        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'name', 'type', 'published']))
            ->from($db->quoteName('#__facileforms_scripts'));
        $scripts = $db->setQuery($query)->loadAssocList('id') ?: [];

        return self::analyse(
            ['id' => $formId, 'name' => (string) $form['name'], 'title' => (string) $form['title'], 'pages' => (int) $form['pages']],
            $elements,
            $scripts
        );
    }

    /**
     * @param array{id: int, name: string, title: string, pages: int} $form
     * @param list<array<string, mixed>>                                $elements Rows of #__facileforms_elements.
     * @param array<int|string, array<string, mixed>>                   $scripts  Library scripts, by id.
     * @return array<string, mixed>
     */
    public static function analyse(array $form, array $elements, array $scripts): array
    {
        $issues = [];
        $suggestions = [];
        $names = [];

        foreach ($elements as $element) {
            $name = trim((string) ($element['name'] ?? ''));
            if ($name !== '') {
                $names[$name][] = (int) $element['id'];
            }
        }

        foreach ($elements as $element) {
            $type = (string) ($element['type'] ?? '');
            $name = trim((string) ($element['name'] ?? ''));
            $title = trim((string) ($element['title'] ?? ''));
            $page = (int) ($element['page'] ?? 0);
            $about = ['element_id' => (int) $element['id'], 'element' => $name !== '' ? $name : $title, 'type' => $type];
            $isInput = \in_array($type, self::INPUT_TYPES, true);

            if ($isInput && $name === '') {
                $issues[] = ['severity' => 'error', 'code' => 'missing_name'] + $about
                    + ['message' => 'The element has no name: its value is not stored with the record.'];
            }
            if ($name !== '' && \count($names[$name]) > 1 && $names[$name][0] === (int) $element['id']) {
                $issues[] = ['severity' => 'error', 'code' => 'duplicate_name'] + $about
                    + ['element_ids' => $names[$name], 'message' => 'Several elements have this name: one value overwrites the other in the record.'];
            }
            if ($isInput && $type !== 'Hidden Input' && $title === '') {
                $issues[] = ['severity' => 'warning', 'code' => 'missing_label'] + $about
                    + ['message' => 'The element has no label: the visitor and the records list show no name for it.'];
            }
            if ($page < 1 || ($form['pages'] > 0 && $page > $form['pages'])) {
                $issues[] = ['severity' => 'warning', 'code' => 'page_out_of_range'] + $about
                    + ['page' => $page, 'message' => sprintf('The element is on page %d, but the form has %d page(s): it is never shown.', $page, $form['pages'])];
            }

            foreach (self::SCRIPT_SLOTS as $slot => [$role, $libraryType]) {
                $condition = (int) ($element['script' . $slot . 'cond'] ?? 0);
                if ($condition === 1) {
                    $script = $scripts[(int) ($element['script' . $slot . 'id'] ?? 0)] ?? null;
                    $problem = match (true) {
                        $script === null => 'no longer exists',
                        (string) $script['type'] !== $libraryType => 'is a "' . $script['type'] . '" script, not an "' . $libraryType . '" one',
                        (int) $script['published'] !== 1 => 'is unpublished',
                        default => null,
                    };
                    if ($problem !== null) {
                        $issues[] = ['severity' => 'error', 'code' => 'library_script_' . $role] + $about
                            + ['script_id' => (int) ($element['script' . $slot . 'id'] ?? 0), 'message' => sprintf('The library %s script of the element %s: it does not run.', $role, $problem)];
                    }
                } elseif ($condition === 2 && (int) ($element['script' . $slot . 'custom'] ?? 0) === 0) {
                    $issues[] = ['severity' => 'warning', 'code' => 'empty_custom_' . $role] + $about
                        + ['message' => sprintf('The element uses a custom %s script, but its code is empty.', $role)];
                }
            }

            if ($type === 'Text' && (int) ($element['script3cond'] ?? 0) === 0 && (int) ($element['published'] ?? 0) === 1) {
                $words = mb_strtolower($name . ' ' . $title);
                foreach (self::SUGGESTIONS as $validation => $pattern) {
                    $script = self::scriptNamed($scripts, $validation);
                    if ($script !== null && preg_match($pattern, $words) === 1) {
                        $suggestions[] = $about + [
                            'validation' => $validation,
                            'script_id' => (int) $script['id'],
                            'reason' => 'Its name or label suggests this kind of value, and it has no validation.',
                        ];
                        break;
                    }
                }
            }
        }

        $errors = \count(array_filter($issues, static fn (array $issue): bool => $issue['severity'] === 'error'));

        return [
            'form' => $form,
            'summary' => [
                'elements' => \count($elements),
                'errors' => $errors,
                'warnings' => \count($issues) - $errors,
                'suggestions' => \count($suggestions),
            ],
            'issues' => \array_slice($issues, 0, self::MAX_ISSUES),
            'issues_truncated' => \count($issues) > self::MAX_ISSUES,
            'suggestions' => \array_slice($suggestions, 0, self::MAX_ISSUES),
        ];
    }

    /**
     * @param array<int|string, array<string, mixed>> $scripts
     * @return array<string, mixed>|null A published library validation of this name.
     */
    private static function scriptNamed(array $scripts, string $name): ?array
    {
        foreach ($scripts as $script) {
            if ((string) $script['name'] === $name && (string) $script['type'] === 'Element Validation' && (int) $script['published'] === 1) {
                return $script;
            }
        }

        return null;
    }
}
