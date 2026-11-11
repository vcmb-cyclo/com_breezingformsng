import { test, expect, request } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const CONTAINER = process.env.BFNG_E2E_CONTAINER;
const ADMIN = 'administrator/index.php';
const MCP = 'api/index.php/v1/breezingformsng/mcp';

function db(...args) {
    return execFileSync('docker', ['exec', '-i', CONTAINER, 'php', '--', ...args], {
        input: readFileSync(new URL('./db.php', import.meta.url)),
    }).toString();
}

const plugin = (...state) => JSON.parse(db('plugin', 'webservices', 'breezingformsng', ...state));

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

    test.beforeAll(async ({ baseURL, browser }) => {
        api = await request.newContext({ baseURL });
        token = db('api-token', process.env.BFNG_E2E_USER);
        page = await browser.newPage();
        await page.route('**/plg_system_guidedtours/**', (route) => route.abort());
        await page.goto(ADMIN);
        await page.fill('#mod-login-username', process.env.BFNG_E2E_USER);
        await page.fill('#mod-login-password', process.env.BFNG_E2E_PASSWORD);
        await Promise.all([page.waitForNavigation(), page.click('#btn-login-submit')]);
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
        expect(tools.map((tool) => tool.name)).toEqual(['bfng_list_forms', 'bfng_get_form']);

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
