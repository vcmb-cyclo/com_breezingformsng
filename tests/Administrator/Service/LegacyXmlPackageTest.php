<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\LegacyXmlPackage;

/**
 * A package exported as XML by BreezingForms or FacileForms is read as a BFNG
 * package file, with the values the database stores.
 */
final class LegacyXmlPackageTest extends TestCase
{
    private const QUICKMODE = '{"properties":{"type":"root"},"children":[{"properties":{"type":"element","dbId":101}}]}';

    public function testScriptsPiecesFormsAndMenusAreRead(): void
    {
        $stored = base64_encode(self::QUICKMODE);
        $xml = "\xEF\xBB\xBF" . '<?xml version="1.0" encoding="utf-8" ?>
<FacileFormsPackage id="old_pkg" type="autoincrement" version="1.7.5">
    <name>Old package</name>
    <title>Old</title>
    <author>Someone</author>
    <authorEmail>someone@example.org</authorEmail>
    <creationDate>2014-01-02 00:00:00</creationDate>
    <script id="7">
        <package>old_pkg</package>
        <name>ff_check</name>
        <title>Check</title>
        <type>Element Validation</type>
        <code>function ff_check(e)\x7B return e.value !== \x27\x27\x3B \x7D\r\n</code>
    </script>
    <piece id="3">
        <name>ff_before</name>
        <code>$x = 1\x3B</code>
    </piece>
    <form id="12">
        <package>old_pkg</package>
        <name>contact</name>
        <title>Contact\x2C us</title>
        <script1cond>1</script1cond>
        <script1id>7</script1id>
        <script1name>ff_check</script1name>
        <template_code>' . base64_encode($stored) . '</template_code>
        <template_code_processed>' . base64_encode('QuickMode') . '</template_code_processed>
        <template_areas>[{"elements":[{"dbId":101}]}]</template_areas>
        <element id="101">
            <name>email</name>
            <title>E-mail</title>
            <type>Text</type>
            <script3cond>1</script3cond>
            <script3id>7</script3id>
        </element>
        <element>
            <name>send</name>
        </element>
    </form>
    <compmenu id="1">
        <title>Main</title>
        <compmenu id="2">
            <title>Sub</title>
            <name>contact</name>
        </compmenu>
    </compmenu>
</FacileFormsPackage>';

        self::assertTrue(LegacyXmlPackage::isXml($xml));
        $payload = LegacyXmlPackage::toPayload($xml);

        self::assertSame('breezingformsng-package', $payload['format']);
        self::assertSame(2, $payload['version']);
        self::assertSame('old_pkg', $payload['package']);
        self::assertSame(['name' => 'Old package', 'title' => 'Old', 'author' => 'Someone', 'email' => 'someone@example.org'], $payload['metadata']);

        self::assertSame(7, $payload['scripts'][0]['source_id']);
        // Escaped line breaks are kept, as the script import of the installer does.
        self::assertSame("function ff_check(e){ return e.value !== ''; }\r\n", $payload['scripts'][0]['data']['code']);
        self::assertSame('Element Validation', $payload['scripts'][0]['data']['type']);
        self::assertSame(['published' => '1', 'name' => 'ff_before', 'type' => 'Untyped', 'code' => '$x = 1;'], $payload['pieces'][0]['data']);

        $form = $payload['forms'][0];
        self::assertSame(12, $form['source_id']);
        self::assertSame('Contact, us', $form['data']['title']);
        self::assertSame('7', $form['data']['script1id']);
        self::assertArrayNotHasKey('script1name', $form['data']);
        // One base64 layer is removed: the value is the one the database stores.
        self::assertSame($stored, $form['data']['template_code']);
        self::assertSame('QuickMode', $form['data']['template_code_processed']);
        self::assertSame('1', $form['data']['pages']);

        self::assertSame(101, $form['elements'][0]['source_id']);
        self::assertSame('7', $form['elements'][0]['script3id']);
        self::assertSame('1', $form['elements'][0]['ordering']);
        // Without an id of its own, an element gets one no file gives.
        self::assertGreaterThan(999_999_999, $form['elements'][1]['source_id']);
        self::assertSame('Static Text/HTML', $form['elements'][1]['type']);
        self::assertSame('2', $form['elements'][1]['ordering']);

        self::assertCount(2, $payload['menus']);
        self::assertSame(0, $payload['menus'][0]['data']['parent']);
        self::assertSame($payload['menus'][0]['source_id'], $payload['menus'][1]['data']['parent']);
        self::assertSame('contact', $payload['menus'][1]['data']['name']);
    }

    public function testAFormWithoutTemplateCodeHasNone(): void
    {
        $payload = LegacyXmlPackage::toPayload('<FacileFormsPackage id="p"><form id="1"><name>f</name><template_areas>x</template_areas></form></FacileFormsPackage>');

        self::assertSame('', $payload['forms'][0]['data']['template_code']);
        self::assertSame('', $payload['forms'][0]['data']['template_areas']);
        self::assertSame([], $payload['forms'][0]['elements']);
    }

    public function testOtherFilesAreRefused(): void
    {
        self::assertFalse(LegacyXmlPackage::isXml('{"format":"breezingformsng-package"}'));

        $refused = [
            '<other id="p"/>',
            '<FacileFormsPackage><title>No id nor name</title></FacileFormsPackage>',
            '<FacileFormsPackage id="p"><form><template_code>not base64!</template_code></form></FacileFormsPackage>',
            '<FacileFormsPackage id="p">',
            '<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><FacileFormsPackage id="&e;"/>',
        ];
        foreach ($refused as $xml) {
            try {
                $payload = LegacyXmlPackage::toPayload($xml);
                // An external entity is never read.
                self::assertStringNotContainsString('root:', (string) json_encode($payload));
            } catch (\UnexpectedValueException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
