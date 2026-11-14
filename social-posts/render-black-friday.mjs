import { spawn } from 'node:child_process';
import { mkdir, mkdtemp, rm, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');

const chrome =
    process.env.CHROME_PATH ||
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const root = path.dirname(fileURLToPath(import.meta.url));
const pageUrl = process.env.BF_URL || 'http://127.0.0.1:8765/social-posts/black-friday.html';
const boards = ['bf-01-date-drop', 'bf-02-bouquet', 'bf-03-lineup'];

const userDataDir = await mkdtemp(path.join(tmpdir(), 'wellth-bf-'));
const profile = path.join(userDataDir, 'profile');
await mkdir(profile, { recursive: true });

const debugPort = 9222 + Math.floor(Math.random() * 400);
const chromeProcess = spawn(
    chrome,
    [
        `--remote-debugging-port=${debugPort}`,
        `--user-data-dir=${profile}`,
        '--no-first-run',
        '--no-default-browser-check',
        '--disable-extensions',
        '--hide-scrollbars',
        '--window-size=1280,1400',
        'about:blank',
    ],
    { stdio: ['ignore', 'ignore', 'pipe'] },
);

async function waitForDebugger(timeoutMs = 20000) {
    const started = Date.now();

    while (Date.now() - started < timeoutMs) {
        try {
            const response = await fetch(`http://127.0.0.1:${debugPort}/json/version`);
            if (response.ok) {
                return;
            }
        } catch {
            // booting
        }

        await new Promise((resolve) => setTimeout(resolve, 200));
    }

    throw new Error('Chrome debugging port did not open.');
}

function connect(wsUrl) {
    return new Promise((resolve, reject) => {
        const socket = new WebSocket(wsUrl);
        let nextId = 1;
        const pending = new Map();
        const eventWaiters = new Map();

        socket.on('open', () => {
            const send = (method, params = {}) => {
                const id = nextId++;

                return new Promise((res, rej) => {
                    pending.set(id, { res, rej });
                    socket.send(JSON.stringify({ id, method, params }));
                });
            };

            const once = (event) =>
                new Promise((res) => {
                    const list = eventWaiters.get(event) || [];
                    list.push(res);
                    eventWaiters.set(event, list);
                });

            resolve({ socket, send, once });
        });

        socket.on('message', (raw) => {
            const message = JSON.parse(String(raw));

            if (message.method && eventWaiters.has(message.method)) {
                const waiters = eventWaiters.get(message.method) || [];
                eventWaiters.delete(message.method);
                waiters.forEach((res) => res(message.params));
            }

            if (!message.id || !pending.has(message.id)) {
                return;
            }

            const { res, rej } = pending.get(message.id);
            pending.delete(message.id);

            if (message.error) {
                rej(new Error(message.error.message || 'CDP error'));
            } else {
                res(message.result);
            }
        });

        socket.on('error', reject);
    });
}

try {
    await waitForDebugger();

    const targets = await (await fetch(`http://127.0.0.1:${debugPort}/json/list`)).json();
    const page = targets.find((target) => target.type === 'page' && target.webSocketDebuggerUrl);

    if (!page) {
        throw new Error('No page target.');
    }

    const { socket, send, once } = await connect(page.webSocketDebuggerUrl);

    await send('Page.enable');
    await send('Runtime.enable');
    await send('Emulation.setDeviceMetricsOverride', {
        width: 1200,
        height: 1400,
        deviceScaleFactor: 2,
        mobile: false,
    });
    const loaded = once('Page.loadEventFired');
    await send('Page.navigate', { url: pageUrl });
    await Promise.race([
        loaded,
        new Promise((resolve) => setTimeout(resolve, 5000)),
    ]);
    await new Promise((resolve) => setTimeout(resolve, 1500));
    await send('Runtime.evaluate', {
        awaitPromise: true,
        expression: `Promise.all([
            document.fonts.ready,
            ...[...document.images].map((img) => img.decode?.().catch(() => {}) || Promise.resolve())
        ])`,
    });

    for (const name of boards) {
        const { result } = await send('Runtime.evaluate', {
            awaitPromise: true,
            returnByValue: true,
            expression: `(() => {
                const el = document.querySelector('[data-name="${name}"]');
                if (!el) throw new Error('Missing ${name}');
                el.scrollIntoView({ block: 'center', inline: 'nearest' });
                return new Promise((resolve) => {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            const rect = el.getBoundingClientRect();
                            resolve({
                                x: rect.x,
                                y: rect.y,
                                width: rect.width,
                                height: rect.height,
                            });
                        });
                    });
                });
            })()`,
        });

        await new Promise((resolve) => setTimeout(resolve, 150));

        const box = result.value;
        const shot = await send('Page.captureScreenshot', {
            format: 'png',
            fromSurface: true,
            clip: {
                x: box.x,
                y: box.y,
                width: box.width,
                height: box.height,
                scale: 1,
            },
        });

        const outPath = path.join(root, `${name}.png`);
        await writeFile(outPath, Buffer.from(shot.data, 'base64'));
        console.log(`Wrote ${outPath}`);
    }

    socket.close();
} finally {
    chromeProcess.kill('SIGKILL');
    await rm(userDataDir, { recursive: true, force: true });
}
