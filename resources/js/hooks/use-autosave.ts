import { useCallback, useEffect, useRef, useState } from 'react';

interface UseAutosaveOptions {
    endpoint: string;
    method?: string;
    debounceMs?: number;
}

interface UseAutosaveReturn {
    isSaving: boolean;
    lastSaved: Date | null;
    error: string | null;
    save: (data: Record<string, unknown>) => void;
}

export function useAutosave({ endpoint, method = 'PUT', debounceMs = 2000 }: UseAutosaveOptions): UseAutosaveReturn {
    const [isSaving, setIsSaving] = useState(false);
    const [lastSaved, setLastSaved] = useState<Date | null>(null);
    const [error, setError] = useState<string | null>(null);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    const save = useCallback(
        (data: Record<string, unknown>) => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
            }

            timerRef.current = setTimeout(async () => {
                if (abortRef.current) {
                    abortRef.current.abort();
                }

                abortRef.current = new AbortController();
                setIsSaving(true);
                setError(null);

                try {
                    const xsrfToken = document.cookie
                        .split('; ')
                        .find((c) => c.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1];

                    const response = await fetch(endpoint, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-XSRF-TOKEN': xsrfToken ? decodeURIComponent(xsrfToken) : '',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(data),
                        signal: abortRef.current.signal,
                    });

                    if (response.ok) {
                        setLastSaved(new Date());
                        setError(null);
                    } else {
                        setError('Speichern fehlgeschlagen');
                    }
                } catch (err) {
                    if (err instanceof DOMException && err.name === 'AbortError') {
                        return;
                    }
                    setError('Speichern fehlgeschlagen');
                } finally {
                    setIsSaving(false);
                }
            }, debounceMs);
        },
        [endpoint, method, debounceMs],
    );

    useEffect(() => {
        return () => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
            }
            if (abortRef.current) {
                abortRef.current.abort();
            }
        };
    }, []);

    return { isSaving, lastSaved, error, save };
}
