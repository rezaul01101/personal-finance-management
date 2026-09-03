import { Download, MonitorSmartphone, Share, WifiOff } from 'lucide-react';
import InstallAppButton from '@/components/install-app-button';
import { Card, CardContent } from '@/components/ui/card';
import { usePwaInstall } from '@/hooks/use-pwa-install';

const benefits = [
    { icon: MonitorSmartphone, label: 'Launches from your home screen' },
    { icon: WifiOff, label: 'Opens instantly, even offline' },
    { icon: Share, label: 'No app store required' },
] as const;

export default function PwaInstallSection() {
    const { isInstallable, isInstalled, isIos } = usePwaInstall();

    if (isInstalled) {
        return null;
    }

    return (
        <section className="mx-auto w-full max-w-5xl px-6 py-16">
            <Card className="border-primary/15 bg-accent/40 overflow-hidden">
                <CardContent className="flex flex-col items-center gap-6 text-center sm:flex-row sm:text-left">
                    <div className="bg-primary/10 text-primary flex size-16 shrink-0 items-center justify-center rounded-2xl">
                        <Download className="size-8" />
                    </div>
                    <div className="flex-1">
                        <h2 className="text-xl font-semibold">
                            Install Personal Finance Management
                        </h2>
                        <p className="text-muted-foreground mt-1">
                            Add the app to your home screen for a faster,
                            full-screen experience.
                        </p>
                        <ul className="text-muted-foreground mt-4 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm sm:justify-start">
                            {benefits.map(({ icon: Icon, label }) => (
                                <li
                                    key={label}
                                    className="flex items-center gap-2"
                                >
                                    <Icon className="text-primary size-4" />
                                    {label}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="shrink-0">
                        {isInstallable ? (
                            <InstallAppButton size="lg" />
                        ) : isIos ? (
                            <p className="text-muted-foreground max-w-[220px] text-sm">
                                Tap{' '}
                                <Share className="inline size-3.5 align-text-bottom" />{' '}
                                Share, then "Add to Home Screen".
                            </p>
                        ) : (
                            <p className="text-muted-foreground max-w-[220px] text-sm">
                                Use your browser's menu and choose "Install
                                app" or "Add to Home Screen".
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>
        </section>
    );
}
