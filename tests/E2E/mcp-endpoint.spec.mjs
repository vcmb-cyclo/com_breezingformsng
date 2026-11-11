import { test, expect, request } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const CONTAINER = process.env.BFNG_E2E_CONTAINER;
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

    // As an MCP client sends a message: JSON-RPC over POST.
    const send = (message, headers = {}) => api.post(MCP, {
        data: { jsonrpc: '2.0', ...message },
        headers: {
            Accept: 'application/json, text/event-stream',
            Authorization: `Bearer ${token}`,
            ...headers,
        },
    });

    test.beforeAll(async ({ baseURL }) => {
        api = await request.newContext({ baseURL });
        token = db('api-token', process.env.BFNG_E2E_USER);
    });

    test.afterAll(async () => {
        await api.dispose();
    });

    test('the web services plugin is installed disabled, and the endpoint absent', async () => {
        expect(plugin()).toEqual({ enabled: '0' });
        // Joomla finds no route; it answers 406 to a client that accepts no JSON:API.
        expect([404, 406]).toContain((await send({ id: 1, method: 'ping' })).status());
    });

    test('an enabled endpoint asks for a valid API token', async () => {
        plugin('1');

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

        const list = (await (await send({
            id: 3,
            method: 'tools/call',
            params: { name: 'bfng_list_forms', arguments: { limit: 100 } },
        })).json()).result;
        expect(list.isError).toBe(false);
        expect(list.structuredContent.total).toBeGreaterThan(0);
        const [first] = list.structuredContent.forms;

        const form = (await (await send({
            id: 4,
            method: 'tools/call',
            params: { name: 'bfng_get_form', arguments: { id: first.id } },
        })).json()).result;
        expect(form.isError).toBe(false);
        expect(form.structuredContent.name).toBe(first.name);
        expect(form.structuredContent.elements).toHaveLength(first.element_count);
        expect(JSON.stringify(form)).not.toMatch(/mailchimp|salesforce|dropbox|emailadr/);
    });

    test('a request from another site, or a stream, is refused', async () => {
        expect((await send({ id: 1, method: 'ping' }, { Origin: 'https://attacker.example' })).status()).toBe(403);
        expect((await api.get(MCP, {
            headers: { Accept: 'text/event-stream', Authorization: `Bearer ${token}` },
        })).status()).toBe(405);
    });

    test('a disabled plugin closes the endpoint again', async () => {
        plugin('0');
        expect([404, 406]).toContain((await send({ id: 1, method: 'ping' })).status());
    });
});
