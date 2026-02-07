import { Component, type ErrorInfo, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
        console.error('ErrorBoundary caught:', error, errorInfo);
    }

    handleReset = () => {
        this.setState({ hasError: false, error: null });
    };

    handleReload = () => {
        window.location.reload();
    };

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex min-h-screen items-center justify-center bg-background p-4">
                    <div className="mx-auto max-w-md space-y-4 text-center">
                        <h1 className="text-2xl font-bold text-foreground">
                            Etwas ist schiefgelaufen
                        </h1>
                        <p className="text-muted-foreground">
                            Ein unerwarteter Fehler ist aufgetreten. Bitte versuche es erneut.
                        </p>
                        {this.state.error && (
                            <details className="rounded-lg border bg-muted/50 p-3 text-left text-xs">
                                <summary className="cursor-pointer font-medium">
                                    Technische Details
                                </summary>
                                <pre className="mt-2 overflow-auto whitespace-pre-wrap text-muted-foreground">
                                    {this.state.error.message}
                                </pre>
                            </details>
                        )}
                        <div className="flex justify-center gap-2">
                            <Button variant="outline" onClick={this.handleReset}>
                                Erneut versuchen
                            </Button>
                            <Button onClick={this.handleReload}>
                                Seite neu laden
                            </Button>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
