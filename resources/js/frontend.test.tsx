import { http, router, type HttpRequestConfig, type HttpResponse } from '@inertiajs/core';
import { client as precognitionClient } from 'laravel-precognition';
import { act, StrictMode, useEffect, type ReactNode } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { I18nextProvider } from 'react-i18next';
import { toast } from 'sonner';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import DiscordLink from '@/components/discord-link';
import GithubLink from '@/components/github-link';
import LanguageSwitcher from '@/components/language-switcher';
import { useLaravelForm } from '@/hooks/use-laravel-generator';
import { initI18n } from '@/i18n';
import Generator from '@/pages/generator';
import Show from '@/pages/generator/show';

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    Head: () => null,
    usePoll: () => ({ stop: () => {} }),
}));

vi.mock('sonner', () => ({ toast: { info: vi.fn(), error: vi.fn(), warning: vi.fn(), success: vi.fn() }, Toaster: () => null }));

type PendingRequest = {
    config: HttpRequestConfig;
    resolve: (response: HttpResponse) => void;
    reject: (reason: Error) => void;
};

let requests: PendingRequest[];
let validationRequests: HttpRequestConfig[];
let container: HTMLDivElement;
let root: Root;
let form: ReturnType<typeof useLaravelForm>;

function FormHarness() {
    const currentForm = useLaravelForm();
    useEffect(() => {
        form = currentForm;
    });
    return <output>{JSON.stringify(currentForm.data)}</output>;
}

async function render(children: ReactNode) {
    const i18n = initI18n('en', { en: { translation: {} } });
    await act(async () => {
        root.render(
            <StrictMode>
                <I18nextProvider i18n={i18n}>{children}</I18nextProvider>
            </StrictMode>,
        );
    });
}

async function selectPackage(packageName: string) {
    await act(async () => {
        form.setData('starter_kit', 'custom');
        form.setData('custom_starter_kit', packageName);
    });
}

function answer(request: PendingRequest, detected: Record<string, unknown>) {
    request.resolve({ status: 200, headers: {}, data: JSON.stringify({ detected }) });
}

async function changeInput(input: HTMLInputElement, value: string) {
    await act(async () => {
        Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')!.set!.call(input, value);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
}

beforeEach(() => {
    vi.clearAllMocks();
    Object.assign(globalThis, { IS_REACT_ACT_ENVIRONMENT: true });
    requests = [];
    validationRequests = [];
    const queue = requests;
    const request = (config: HttpRequestConfig) =>
        new Promise<HttpResponse>((resolve, reject) => {
            queue.push({ config, resolve, reject });
            config.signal?.addEventListener('abort', () => reject(new DOMException('Aborted', 'AbortError')), { once: true });
        });
    http.setClient({ request });
    precognitionClient.useHttpClient({
        request: async (config) => {
            validationRequests.push(config);
            return { status: 204, data: {}, headers: { precognition: 'true', 'precognition-success': 'true' } };
        },
    });
    vi.spyOn(window, 'scrollTo').mockImplementation(() => {});
    container = document.createElement('div');
    document.body.append(container);
    root = createRoot(container);
});

afterEach(async () => {
    await act(async () => root.unmount());
    container.remove();
    vi.restoreAllMocks();
    vi.useRealTimers();
});

describe('custom package detection', () => {
    it('keeps the latest package configuration when responses arrive out of order', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/first');
        let first: Promise<void>;
        await act(async () => {
            first = form.detectDependencies('vendor/first');
        });
        await selectPackage('vendor/second');
        let second: Promise<void>;
        await act(async () => {
            second = form.detectDependencies('vendor/second');
        });
        await act(async () => {
            answer(requests[1], { database: 'pgsql' });
            await second;
        });
        await act(async () => {
            answer(requests[0], { database: 'mysql' });
            await first;
        });
        expect(form.data.database).toBe('pgsql');
        expect(requests[0].config.signal?.aborted).toBe(true);
    });

    it('does not overwrite edits made while detection is running', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/starter');
        let pending: Promise<void>;
        await act(async () => {
            pending = form.detectDependencies('vendor/starter');
        });
        await act(async () => form.setData('database', 'mariadb'));
        await act(async () => {
            answer(requests[0], { database: 'mysql', php_version: '8.4' });
            await pending;
        });
        expect(form.data.database).toBe('mariadb');
        expect(form.data.php_version).toBe('8.4');
    });

    it('preserves an edit made in the same turn as the detection response', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/starter');
        let pending: Promise<void>;
        await act(async () => {
            pending = form.detectDependencies('vendor/starter');
        });
        await act(async () => {
            form.setData('database', 'mariadb');
            answer(requests[0], { database: 'mysql' });
            await pending;
        });
        expect(form.data.database).toBe('mariadb');
    });

    it('discards a response when the starter kit changes in the same turn', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/starter');
        let pending: Promise<void>;
        await act(async () => {
            pending = form.detectDependencies('vendor/starter');
        });
        await act(async () => {
            form.setData('starter_kit', 'none');
            answer(requests[0], { database: 'mysql' });
            await pending;
        });
        expect(form.data.database).toBe('sqlite');
        expect(toast.info).not.toHaveBeenCalled();
    });

    it('preserves a queue configuration edited while detection is running', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/starter');
        await act(async () => {
            form.setData('queue_type', 'native');
            form.setData('queue_driver', 'redis');
        });
        let pending: Promise<void>;
        await act(async () => {
            pending = form.detectDependencies('vendor/starter');
        });
        await act(async () => form.setData('queue_driver', 'database'));
        await act(async () => {
            answer(requests[0], { queue_type: 'horizon', queue_driver: 'redis' });
            await pending;
        });
        expect(form.data.queue_type).toBe('native');
        expect(form.data.queue_driver).toBe('database');
    });

    it('reports detection warnings without replacing an uncertain PHP version', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/starter');
        let pending: Promise<void>;
        await act(async () => {
            pending = form.detectDependencies('vendor/starter');
        });
        await act(async () => {
            answer(requests[0], { notifications: [{ type: 'warning', message: 'Choose the PHP version manually.' }] });
            await pending;
        });
        expect(form.data.php_version).toBe('8.5');
        expect(toast.warning).toHaveBeenCalledWith('Choose the PHP version manually.');
    });

    it('exposes package validation errors and permits a retry', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/starter');
        let pending: Promise<void>;
        await act(async () => {
            pending = form.detectDependencies('vendor/starter');
        });
        await act(async () => {
            requests[0].resolve({ status: 422, headers: {}, data: JSON.stringify({ errors: { package: ['Invalid package.'] } }) });
            await pending;
        });
        expect(form.detectionErrors.package).toBe('Invalid package.');
        await act(async () => {
            void form.detectDependencies('vendor/starter');
        });
        expect(requests).toHaveLength(2);
    });

    it('discards detection after leaving the custom starter kit', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/starter');
        let pending: Promise<void>;
        await act(async () => {
            pending = form.detectDependencies('vendor/starter');
        });
        await act(async () => form.setData('starter_kit', 'none'));
        await act(async () => {
            answer(requests[0], { database: 'mysql' });
            await pending;
        });
        expect(form.data.database).toBe('sqlite');
        expect(requests[0].config.signal?.aborted).toBe(true);
    });

    it('aborts the request when the form unmounts', async () => {
        await render(<FormHarness />);
        await selectPackage('vendor/starter');
        await act(async () => {
            void form.detectDependencies('vendor/starter');
        });
        await act(async () => root.render(null));
        expect(requests[0].config.signal?.aborted).toBe(true);
    });

    it('retries the same package after a failed request', async () => {
        await render(<Generator />);
        const starter = container.querySelector<HTMLSelectElement>('[name="starter_kit"]')!;
        await act(async () => {
            starter.value = 'custom';
            starter.dispatchEvent(new Event('change', { bubbles: true }));
        });
        const input = container.querySelector<HTMLInputElement>('[name="custom_starter_kit"]')!;
        await changeInput(input, 'vendor/starter');
        await act(async () => input.dispatchEvent(new FocusEvent('focusout', { bubbles: true })));
        const detection = requests.find((request) => request.config.method === 'get')!;
        await act(async () => detection.reject(new Error('Network unavailable')));
        await act(async () => input.dispatchEvent(new FocusEvent('focusout', { bubbles: true })));
        expect(requests.filter((request) => request.config.method === 'get')).toHaveLength(2);
    });
});

it('preserves the accessible names of external icon links', async () => {
    await render(
        <>
            <GithubLink />
            <DiscordLink />
        </>,
    );
    expect([...container.querySelectorAll('a')].map((link) => link.getAttribute('aria-label'))).toEqual(['Github', 'Discord']);
});

it('updates the document language when i18next changes language', async () => {
    const i18n = initI18n('en', { en: { translation: {} }, fr: { translation: {} } });
    await act(async () =>
        root.render(
            <I18nextProvider i18n={i18n}>
                <LanguageSwitcher />
            </I18nextProvider>,
        ),
    );
    await act(async () => {
        await i18n.changeLanguage('fr');
    });
    expect(document.documentElement.lang).toBe('fr');
    expect(container.querySelector('[role="img"]')?.getAttribute('aria-label')).toContain('Français');
});

it('submits directly without an extra precognition request', async () => {
    const post = vi.spyOn(router, 'post').mockImplementation(() => {});
    await render(<Generator />);
    await act(async () => {
        container.querySelector('form')!.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });
    expect(post).toHaveBeenCalledOnce();
    expect(requests).toHaveLength(0);
    expect(validationRequests).toHaveLength(0);
});

it('keeps copy feedback for two seconds after the latest copy', async () => {
    vi.useFakeTimers();
    Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: vi.fn().mockResolvedValue(undefined) } });
    await render(<Show template={{ id: 'template', status: 'completed', download_command: 'curl example.test' }} />);
    const copy = container.querySelector('button')!;
    expect(copy.getAttribute('aria-label')).toBeTruthy();
    await act(async () => copy.click());
    await act(async () => vi.advanceTimersByTime(1500));
    await act(async () => copy.click());
    await act(async () => vi.advanceTimersByTime(600));
    expect(copy.querySelector('.lucide-check')).not.toBeNull();
    await act(async () => vi.advanceTimersByTime(1400));
    expect(copy.querySelector('.lucide-copy')).not.toBeNull();
});
