import { Form, Head, Link } from '@inertiajs/react';
import GoogleCalendarController from '@/actions/App/Http/Controllers/Settings/GoogleCalendarController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { show } from '@/routes/google-calendar';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Google Calendar',
        href: show().url,
    },
];

type ConnectionData = {
    connected: boolean;
    connection_type?: string;
    calendar_id?: string;
    enabled?: boolean;
    last_synced_at?: string | null;
};

export default function GoogleCalendar({
    connection,
    oauthConfigured,
}: {
    connection: ConnectionData;
    oauthConfigured: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Google Calendar" />

            <h1 className="sr-only">Google Calendar Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Google Calendar"
                        description="Synchronisiere deine Kalender-Eintr&auml;ge mit Google Calendar"
                    />

                    {connection.connected ? (
                        <ConnectedState connection={connection} />
                    ) : (
                        <DisconnectedState oauthConfigured={oauthConfigured} />
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}

function ConnectedState({ connection }: { connection: ConnectionData }) {
    return (
        <div className="space-y-4">
            <div className="rounded-lg border bg-card p-4">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <svg
                            className="h-5 w-5 text-green-600 dark:text-green-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            strokeWidth={2}
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="font-medium text-sm">Verbunden</p>
                        <p className="text-sm text-muted-foreground truncate">
                            {connection.calendar_id}
                        </p>
                    </div>
                </div>

                <div className="mt-4 space-y-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">
                            Verbindungstyp
                        </span>
                        <span>
                            {connection.connection_type === 'service_account'
                                ? 'Service Account'
                                : 'OAuth2'}
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Status</span>
                        <span>
                            {connection.enabled ? 'Aktiv' : 'Deaktiviert'}
                        </span>
                    </div>
                    {connection.last_synced_at && (
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Letzte Synchronisation
                            </span>
                            <span>
                                {new Date(
                                    connection.last_synced_at,
                                ).toLocaleString('de-DE')}
                            </span>
                        </div>
                    )}
                </div>
            </div>

            <Form
                {...GoogleCalendarController.disconnect.form.delete()}
                options={{ preserveScroll: true }}
            >
                {({ processing }) => (
                    <Button
                        variant="destructive"
                        disabled={processing}
                        type="submit"
                    >
                        {processing
                            ? 'Wird getrennt...'
                            : 'Verbindung trennen'}
                    </Button>
                )}
            </Form>
        </div>
    );
}

function DisconnectedState({
    oauthConfigured,
}: {
    oauthConfigured: boolean;
}) {
    return (
        <div className="space-y-4">
            <div className="rounded-lg border bg-card p-4">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted">
                        <svg
                            className="h-5 w-5 text-muted-foreground"
                            fill="none"
                            viewBox="0 0 24 24"
                            strokeWidth={2}
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                            />
                        </svg>
                    </div>
                    <div>
                        <p className="font-medium text-sm">
                            Nicht verbunden
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Verbinde deinen Google Calendar, um Eintr&auml;ge
                            automatisch zu synchronisieren.
                        </p>
                    </div>
                </div>
            </div>

            {oauthConfigured ? (
                <Button asChild>
                    <Link href={GoogleCalendarController.redirect.url()}>
                        Mit Google Calendar verbinden
                    </Link>
                </Button>
            ) : (
                <p className="text-sm text-muted-foreground">
                    Google OAuth ist nicht konfiguriert. Bitte konfiguriere die
                    Google API Credentials in der .env Datei.
                </p>
            )}
        </div>
    );
}
