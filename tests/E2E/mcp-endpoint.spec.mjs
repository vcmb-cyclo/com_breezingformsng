import { test, expect, request } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { loginAsAdmin } from './login.mjs';

const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const ADMIN = 'administrator/index.php';
const MCP = 'api/index.php/v1/breezingformsng/mcp';

function db(...args) {
    return execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
        input: readFileSync(new URL('./db.php', import.meta.url)),
    }).toString();
}

const plugin = (...state) => JSON.parse(db('plugin', 'webservices', 'breezingformsng', ...state));
const formRow = (id) => JSON.parse(db('form', String(id)));

test.describe.configure({ mode: 'serial' });

test.describe('MCP endpoint', () => {
    let api;
    let token;
    let page;

    // The "Enable MCP" switch of the BreezingForms NG options.
    // The MCP status of the Configuration page of BreezingForms NG.
    const status = async () => {
        await page.goto(`${ADMIN}?option=com_breezingformsng&view=packages`);
        const card = page.locator('#bf-mcp-status');
        await expect(card.locator('img.bf-mcp-logo:visible')).toHaveCount(1);
        return card.locator('[data-mcp-state]').getAttribute('data-mcp-state');
    };

    const setMcp = async (enabled) => {
        await page.goto(`${ADMIN}?option=com_config&view=component&component=com_breezingformsng`);
        await page.getByRole('tab', { name: 'AI assistants (MCP)' }).click();
        await page.locator(`#jform_mcp_enabled${enabled ? 1 : 0}`).check({ force: true });
        await Promise.all([page.waitForNavigation(), page.locator('joomla-toolbar-button[task="component.apply"] button').click()]);
        await expect(page.locator('joomla-alert[type="success"], .alert-success')).toBeVisible();
    };

    // As an MCP client sends a message: JSON-RPC over POST.
    const send = (message, headers = {}) => api.post(MCP, {
        data: { jsonrpc: '2.0', ...message },
        headers: {
            Accept: 'application/json, text/event-stream',
            Authorization: `Bearer ${token}`,
            ...headers,
        },
    });

    const callTool = async (id, name, args) => {
        const response = await send({ id, method: 'tools/call', params: { name, arguments: args } });
        expect(response.status()).toBe(200);

        return (await response.json()).result;
    };

    test.beforeAll(async ({ baseURL, browser }) => {
        api = await request.newContext({ baseURL });
        token = db('api-token', process.env.BFNG_E2E_USER);
        page = await browser.newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await loginAsAdmin(page);
    });

    test.afterAll(async () => {
        await api.dispose();
        await page.close();
    });

    test('MCP is off after installation: the endpoint does not exist', async () => {
        expect(plugin()).toEqual({ enabled: '1' });
        expect(await status()).toBe('disabled');
        // Joomla finds no route; it answers 406 to a client that accepts no JSON:API.
        expect([404, 406]).toContain((await send({ id: 1, method: 'ping' })).status());
    });

    test('enabled in the options, the endpoint asks for a valid API token', async () => {
        await setMcp(true);
        expect(await status()).toBe('enabled');

        const anonymous = await api.post(MCP, {
            data: { jsonrpc: '2.0', id: 1, method: 'ping' },
            headers: { Accept: 'application/json, text/event-stream' },
        });
        expect(anonymous.status()).toBe(401);
        expect(anonymous.headers()['www-authenticate']).toBe('Bearer');

        const forged = await send({ id: 1, method: 'ping' }, { Authorization: 'Bearer c2hhMjU2OjE6eHg=' });
        expect(forged.status()).toBe(401);
    });

    test('a client initializes, lists the tools and reads the forms', async () => {
        const initialize = await send({
            id: 1,
            method: 'initialize',
            params: { protocolVersion: '2025-06-18', capabilities: {}, clientInfo: { name: 'e2e', version: '1' } },
        });
        expect(initialize.status()).toBe(200);
        expect((await initialize.json()).result.protocolVersion).toBe('2025-06-18');

        expect((await send({ method: 'notifications/initialized' })).status()).toBe(202);

        const tools = (await (await send({ id: 2, method: 'tools/list' })).json()).result.tools;
        expect(tools.map((tool) => tool.name)).toEqual([
            'bfng_list_forms', 'bfng_get_form', 'bfng_check_form',
            'bfng_create_form', 'bfng_update_form', 'bfng_manage_form_fields',
            'bfng_trash_form', 'bfng_restore_form', 'bfng_audit_database',
        ]);
        // Reading tools are read only; every form-management tool says that it writes.
        const readOnly = ['bfng_list_forms', 'bfng_get_form', 'bfng_check_form', 'bfng_audit_database'];
        for (const tool of tools) {
            expect(tool.annotations.readOnlyHint, tool.name).toBe(readOnly.includes(tool.name));
        }
        // Removing or rewriting fields is flagged as destructive for the client.
        const destructive = tools.filter((tool) => tool.annotations.destructiveHint).map((tool) => tool.name);
        expect(destructive).toEqual(['bfng_manage_form_fields', 'bfng_trash_form']);

        const formId = Number(db('sample-form', 'e2e_mcp'));
        const list = (await (await send({
            id: 3,
            method: 'tools/call',
            params: { name: 'bfng_list_forms', arguments: { search: 'e2e_mcp' } },
        })).json()).result;
        expect(list.isError).toBe(false);
        expect(list.structuredContent.forms).toEqual([expect.objectContaining({
            id: formId, name: 'e2e_mcp', element_count: 1, editor: 'classic',
        })]);

        const form = (await (await send({
            id: 4,
            method: 'tools/call',
            params: { name: 'bfng_get_form', arguments: { name: 'e2e_mcp' } },
        })).json()).result;
        expect(form.isError).toBe(false);
        expect(form.structuredContent.elements.map((element) => element.name)).toEqual(['email']);
        expect(form.structuredContent.settings.notification_address_set).toBe(true);
        // The integration key and the notification address stay on the site.
        expect(JSON.stringify(form)).not.toMatch(/secret-key|owner@example/);

        // The check of a consistent form: no issue, and a proposal for the e-mail field.
        const check = (await (await send({
            id: 5,
            method: 'tools/call',
            params: { name: 'bfng_check_form', arguments: { name: 'e2e_mcp' } },
        })).json()).result;
        expect(check.isError).toBe(false);
        expect(check.structuredContent.summary).toEqual({ elements: 1, errors: 0, warnings: 0, suggestions: 1 });
        expect(check.structuredContent.suggestions).toEqual([expect.objectContaining({
            element: 'email', validation: 'ff_validemail',
        })]);
        expect(JSON.stringify(check)).not.toMatch(/secret-key|owner@example/);

        const unknown = (await (await send({
            id: 6,
            method: 'tools/call',
            params: { name: 'bfng_check_form', arguments: { name: 'no_such_form' } },
        })).json()).result;
        expect(unknown.isError).toBe(true);

        // The audit of the About screen, read only: its counts, no argument.
        const audit = (await (await send({
            id: 7,
            method: 'tools/call',
            params: { name: 'bfng_audit_database', arguments: {} },
        })).json()).result;
        expect(audit.isError).toBe(false);
        expect(audit.structuredContent.summary.expected_tables).toBeGreaterThan(0);
        expect(audit.structuredContent.summary.missing_tables).toBe(0);
        // Each list is cut at 50 items; truncated names the cut ones, the counts stay complete.
        const { truncated, summary, ...lists } = audit.structuredContent;
        for (const [name, items] of Object.entries(lists)) {
            if (Array.isArray(items)) {
                expect(items.length, name).toBeLessThanOrEqual(50);
            }
        }
        // A cut list is named by its path: "duplicate_forms[].drop" is a list inside the items of another one.
        for (const name of truncated.filter((path) => !path.includes('['))) {
            expect(lists[name], name).toHaveLength(50);
        }
        expect(summary.column_collation_issues).toBeGreaterThanOrEqual(lists.column_collation_issues.length);
        expect(JSON.stringify(audit)).not.toMatch(/secret-key|owner@example/);
        // The report gives identifiers and codes, not the place of the site on the server.
        expect(JSON.stringify(audit)).not.toMatch(/\/var\/www|\/home\//);
    });

    test('a client creates, changes, publishes, restores and trashes a QuickMode form', async () => {
        const name = `e2e_mcp_write_${Date.now()}`;
        const created = await callTool(8, 'bfng_create_form', {
            name,
            title: 'MCP write journey',
            description: 'Created through MCP.',
            page_intro: 'Tell us about your request.',
            fields: [
                {
                    type: 'text', name: 'email', label: 'Email', placeholder: 'you@example.invalid',
                    hint: 'We only use this to reply.', required: true, required_message: 'Email is required.', max_length: 254,
                },
                {
                    type: 'select', name: 'topic', label: 'Topic',
                    options: [{ label: 'Support', value: 'support', selected: true }, { label: 'Sales', value: 'sales' }],
                },
                { type: 'text', name: 'phone', label: 'Phone', widget: 'phone' },
            ],
        });
        expect(created.isError).toBe(false);
        expect(created.structuredContent).toEqual(expect.objectContaining({
            name, title: 'MCP write journey', published: false, field_count: 3,
        }));
        const formId = created.structuredContent.id;
        expect(formId).toBeGreaterThan(0);

        const changedFields = await callTool(9, 'bfng_manage_form_fields', {
            id: formId,
            add_pages: 1,
            update_pages: [{ page: 2, intro: 'Describe your request in detail.' }],
            update: [{ name: 'email', label: 'Reply-to email', placeholder: 'reply@example.invalid', widget: 'email' }],
            add: [{
                type: 'textarea', name: 'message', label: 'Message', page: 2,
                default_value: 'Hello', width: '100%', height: '8rem', max_length: 500, required: true,
            }],
            visibility_rules: [{
                target: 'message', source: 'topic', operator: 'equals', value: 'support', visible: true,
            }],
        });
        expect(changedFields.isError).toBe(false);
        expect(changedFields.structuredContent).toEqual(expect.objectContaining({
            id: formId, added: ['message'], updated: ['email'], pages_added: 1, pages_updated: [2],
            visibility_rules_updated: true, visibility_rule_count: 1, field_count: 4,
        }));

        const updated = await callTool(10, 'bfng_update_form', {
            id: formId,
            title: 'Published MCP write journey',
            widthmode: 1,
            width: 90,
            email_notifications: true,
            notification_recipient: 'mcp@example.invalid',
            published: true,
        });
        expect(updated.isError).toBe(false);
        expect(updated.structuredContent).toEqual(expect.objectContaining({
            id: formId, title: 'Published MCP write journey', published: true,
            settings: expect.objectContaining({ width: 90, widthmode: 1, email_notifications: true }),
        }));

        const saved = await callTool(11, 'bfng_get_form', { id: formId });
        expect(saved.isError).toBe(false);
        expect(saved.structuredContent).toEqual(expect.objectContaining({
            id: formId, editor: 'quickmode', published: true, pages: 2,
            settings: expect.objectContaining({ notification_address_set: true, width: 90, widthmode: 1 }),
            quickmode_pages: expect.arrayContaining([{ page: 2, intro_text: 'Describe your request in detail.', intro_is_rich: false }]),
            visibility_rules: [{
                target: 'message', source: 'topic', operator: 'equals', value: 'support', visible: true,
            }],
            visibility_rules_supported: true,
        }));
        expect(saved.structuredContent.elements).toEqual(expect.arrayContaining([
            expect.objectContaining({
                name: 'email', title: 'Reply-to email',
                quickmode_settings: expect.objectContaining({
                    placeholder: 'reply@example.invalid', required: true, widget: 'email',
                }),
            }),
            expect.objectContaining({
                name: 'message', title: 'Message', page: 2,
                quickmode_settings: expect.objectContaining({
                    initial_value: 'Hello', width: '100%', height: '8rem', maximum_length: 500, required: true,
                }),
            }),
            expect.objectContaining({
                name: 'phone', title: 'Phone',
                quickmode_settings: expect.objectContaining({ widget: 'phone' }),
            }),
        ]));
        expect(JSON.stringify(saved)).not.toContain('mcp@example.invalid');
        expect(formRow(formId).history).toBeGreaterThan(0);

        const trashed = await callTool(12, 'bfng_trash_form', { id: formId });
        expect(trashed.structuredContent).toEqual({ id: formId, state: 'trashed' });
        const restored = await callTool(13, 'bfng_restore_form', { id: formId });
        expect(restored.structuredContent).toEqual({ id: formId, state: 'unpublished' });

        const restoredForm = await callTool(14, 'bfng_get_form', { id: formId });
        expect(restoredForm.isError).toBe(false);
        expect(restoredForm.structuredContent.published).toBe(false);

        // The throwaway Joomla is left without a visible form created by this journey.
        const cleanedUp = await callTool(15, 'bfng_trash_form', { id: formId });
        expect(cleanedUp.structuredContent).toEqual({ id: formId, state: 'trashed' });
    });

    test('legacy protocol batches are accepted but newer protocol batches are rejected', async () => {
        const batch = [
            { jsonrpc: '2.0', id: 1, method: 'ping' },
            { jsonrpc: '2.0', method: 'notifications/initialized' },
            { jsonrpc: '2.0', id: 2, method: 'tools/list' },
        ];
        const headers = { Accept: 'application/json, text/event-stream', Authorization: `Bearer ${token}` };
        const legacy = await api.post(MCP, { data: batch, headers });
        expect(legacy.status()).toBe(200);
        expect((await legacy.json()).map((entry) => entry.id)).toEqual([1, 2]);
        const recent = await api.post(MCP, { data: batch, headers: { ...headers, 'MCP-Protocol-Version': '2025-11-25' } });
        expect((await recent.json()).error.code).toBe(-32600);
    });

    test('only the endpoint origin is accepted, and a stream is refused', async ({ baseURL }) => {
        const origin = new URL(baseURL);
        expect((await send({ id: 1, method: 'ping' }, { Origin: origin.origin })).status()).toBe(200);
        const otherScheme = new URL(origin.origin);
        otherScheme.protocol = origin.protocol === 'https:' ? 'http:' : 'https:';
        const otherPort = new URL(origin.origin);
        otherPort.port = origin.port === '65534' ? '65533' : '65534';
        for (const invalid of [otherScheme.origin, otherPort.origin, 'null']) {
            expect((await send({ id: 1, method: 'ping' }, { Origin: invalid })).status()).toBe(403);
        }
        expect((await send({ id: 1, method: 'ping' }, { Origin: 'https://attacker.example' })).status()).toBe(403);
        expect((await api.get(MCP, {
            headers: { Accept: 'text/event-stream', Authorization: `Bearer ${token}` },
        })).status()).toBe(405);
    });

    test('a disabled plugin closes the endpoint', async () => {
        plugin('0');
        expect([404, 406]).toContain((await send({ id: 1, method: 'ping' })).status());
        expect(await status()).toBe('disabled');
        await expect(page.locator('#bf-mcp-status')).toContainText('plugin is disabled');
        plugin('1');
        expect((await send({ id: 1, method: 'ping' })).status()).toBe(200);
    });

    test('MCP turned off in the options removes the endpoint completely', async () => {
        await setMcp(false);
        expect(await status()).toBe('disabled');
        expect([404, 406]).toContain((await send({ id: 1, method: 'ping' })).status());
        expect([404, 406]).toContain((await send({ id: 1, method: 'tools/list' })).status());
    });
});
