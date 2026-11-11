<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Reads a package exported as XML by BreezingForms or FacileForms
 * (<FacileFormsPackage>) into the structure of a BFNG package file, which the
 * package import then handles as any other: scripts, pieces, forms with their
 * elements, and component menus.
 *
 * The values are as the legacy exporter wrote them: C-style escapes (\x2C,
 * \n...), and the template code of a form base64-encoded once more than in the
 * database.
 */
final class LegacyXmlPackage
{
    /** The structure produced: the stored values of the version 2 package files. */
    public const PAYLOAD_VERSION = 2;

    /** Ids given to the items without one of their own: above the 9 digits of the ids read. */
    private const GENERATED_IDS = 1_000_000_000;

    private const LIBRARY_FIELDS = ['published', 'package', 'name', 'title', 'description', 'type', 'code', 'unit_tests'];

    private const FORM_FIELDS = [
        'published', 'runmode', 'package', 'name', 'title', 'description', 'class1', 'class2', 'width', 'widthmode',
        'height', 'heightmode', 'pages', 'emailntf', 'emaillog', 'emailxml', 'emailadr', 'dblog', 'prevmode', 'prevwidth',
        'script1cond', 'script1id', 'script1code', 'script2cond', 'script2id', 'script2code',
        'piece1cond', 'piece1id', 'piece1code', 'piece2cond', 'piece2id', 'piece2code',
        'piece3cond', 'piece3id', 'piece3code', 'piece4cond', 'piece4id', 'piece4code',
        'opt_mail', 'double_opt', 'template_areas',
    ];

    /** Defaults of the legacy importer for the values a file leaves out. */
    private const FORM_DEFAULTS = ['published' => '1', 'pages' => '1', 'emailntf' => '1', 'emaillog' => '1', 'dblog' => '1', 'prevmode' => '2'];

    private const ELEMENT_FIELDS = [
        'page', 'published', 'name', 'title', 'type', 'class1', 'class2', 'logging', 'posx', 'posxmode', 'posy', 'posymode',
        'width', 'widthmode', 'height', 'heightmode', 'flag1', 'flag2', 'data1', 'data2', 'data3',
        'script1cond', 'script1id', 'script1code', 'script1flag1', 'script1flag2',
        'script2cond', 'script2id', 'script2code', 'script2flag1', 'script2flag2', 'script2flag3', 'script2flag4', 'script2flag5',
        'script3cond', 'script3id', 'script3code', 'script3msg', 'mailback', 'mailbackfile',
    ];

    private const ELEMENT_DEFAULTS = ['page' => '1', 'published' => '1', 'logging' => '1', 'type' => 'Static Text/HTML'];

    private const MENU_FIELDS = ['published', 'package', 'img', 'title', 'name', 'page', 'frame', 'border', 'params'];

    private const MENU_DEFAULTS = ['published' => '1', 'page' => '1'];

    /** Package details of the file, as the package export writes them. */
    private const METADATA = [
        'name' => 'name', 'title' => 'title', 'version' => 'version', 'author' => 'author',
        'authorEmail' => 'email', 'authorUrl' => 'url', 'description' => 'description', 'copyright' => 'copyright',
    ];

    /** Whether the contents look like XML rather than JSON. */
    public static function isXml(string $contents): bool
    {
        return str_starts_with(ltrim($contents, "\xEF\xBB\xBF \t\r\n"), '<');
    }

    /**
     * @return array<string, mixed>
     * @throws \UnexpectedValueException when the file is not a readable legacy package.
     */
    public static function toPayload(string $contents): array
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        // No network access, and no entity expansion: LIBXML_NOENT is not given.
        $xml = simplexml_load_string($contents, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if ($xml === false || $xml->getName() !== 'FacileFormsPackage') {
            throw new \UnexpectedValueException('Not a FacileForms XML package.');
        }

        $package = trim((string) ($xml['id'] ?? ''));
        $package = $package !== '' ? $package : self::text($xml->name);
        if ($package === '') {
            throw new \UnexpectedValueException('The package has no id.');
        }

        $metadata = [];
        foreach (self::METADATA as $tag => $key) {
            if (isset($xml->{$tag})) {
                $metadata[$key] = self::text($xml->{$tag});
            }
        }

        $payload = [
            'format' => 'breezingformsng-package',
            'version' => self::PAYLOAD_VERSION,
            'package' => $package,
            'scripts' => self::libraries($xml->script),
            'pieces' => self::libraries($xml->piece),
            'menus' => [],
            'forms' => [],
            'metadata' => $metadata,
        ];

        $formIds = [];
        $elementIds = [];
        foreach ($xml->form as $node) {
            $payload['forms'][] = self::form($node, $formIds, $elementIds);
        }

        $menuIds = [];
        foreach ($xml->compmenu as $node) {
            // The legacy menus have one level of submenus.
            $sourceId = self::take($menuIds, null);
            $payload['menus'][] = ['source_id' => $sourceId, 'data' => self::values($node, self::MENU_FIELDS, self::MENU_DEFAULTS) + ['parent' => 0]];
            foreach ($node->compmenu as $child) {
                $payload['menus'][] = [
                    'source_id' => self::take($menuIds, null),
                    'data' => self::values($child, self::MENU_FIELDS, self::MENU_DEFAULTS) + ['parent' => $sourceId],
                ];
            }
        }

        return $payload;
    }

    /**
     * @return list<array{source_id: int, data: array<string, string>}>
     */
    private static function libraries(\SimpleXMLElement $nodes): array
    {
        $ids = [];
        $rows = [];
        foreach ($nodes as $node) {
            // The forms point to their scripts and pieces by these ids.
            $rows[] = ['source_id' => self::take($ids, self::id($node)), 'data' => self::values($node, self::LIBRARY_FIELDS, ['published' => '1', 'type' => 'Untyped'])];
        }

        return $rows;
    }

    /**
    /**
     * @param array<int, true> $formIds
     * @param array<int, true> $elementIds
     * @return array{source_id: int, data: array<string, string>, elements: list<array<string, mixed>>}
     */
    private static function form(\SimpleXMLElement $node, array &$formIds, array &$elementIds): array
    {
        $data = self::values($node, self::FORM_FIELDS, self::FORM_DEFAULTS);
        $data['template_code'] = '';
        $data['template_code_processed'] = '';
        $code = self::text($node->template_code);
        if ($code !== '') {
            // One base64 layer more than in the database.
            $stored = base64_decode($code, true);
            $processed = base64_decode(self::text($node->template_code_processed), true);
            if ($stored === false || $processed === false) {
                throw new \UnexpectedValueException('The template code of a form is not base64.');
            }
            $data['template_code'] = $stored;
            $data['template_code_processed'] = $processed;
        } else {
            $data['template_areas'] = '';
        }

        $elements = [];
        foreach ($node->element as $element) {
            // The QuickMode template code of the form points to its elements by these ids.
            $elements[] = ['source_id' => self::take($elementIds, self::id($element))]
                + self::values($element, self::ELEMENT_FIELDS, self::ELEMENT_DEFAULTS)
                + ['ordering' => (string) (count($elements) + 1)];
        }

        return ['source_id' => self::take($formIds, self::id($node)), 'data' => $data, 'elements' => $elements];
    }

    /**
     * @param list<string>          $fields
     * @param array<string, string> $defaults
     * @return array<string, string>
     */
    private static function values(\SimpleXMLElement $node, array $fields, array $defaults): array
    {
        $values = [];
        foreach ($fields as $field) {
            if (isset($node->{$field})) {
                $values[$field] = self::text($node->{$field});
            } elseif (isset($defaults[$field])) {
                $values[$field] = $defaults[$field];
            }
        }

        return $values;
    }

    private static function id(\SimpleXMLElement $node): ?int
    {
        $id = trim((string) ($node['id'] ?? ''));

        return preg_match('/^[1-9]\d{0,8}$/', $id) === 1 ? (int) $id : null;
    }

    /**
     * The id of the file when it has one not used yet, else one above every id
     * a file can give, so that none is shared.
     *
     * @param array<int, true> $used
     */
    private static function take(array &$used, ?int $preferred): int
    {
        $id = $preferred !== null && !isset($used[$preferred]) ? $preferred : self::GENERATED_IDS + count($used);
        while (isset($used[$id])) {
            $id++;
        }
        $used[$id] = true;

        return $id;
    }

    /** The legacy exporter stores the values with C-style escapes. */
    private static function text(?\SimpleXMLElement $node): string
    {
        return $node === null || !isset($node[0]) ? '' : stripcslashes(trim((string) $node));
    }
}
