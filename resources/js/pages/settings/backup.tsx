import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import backup from '@/routes/backup';

export default function Backup() {
    return (
        <>
            <Head title="Backup settings" />

            <h1 className="sr-only">Backup settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Backup"
                    description="Download a full SQL backup of your data"
                />

                <Card>
                    <CardContent className="space-y-4">
                        <p className="text-muted-foreground text-sm">
                            Downloads every table as a single .sql file you
                            can restore with{' '}
                            <code>mysql &lt; backup.sql</code>. Session data
                            and password reset tokens are left out.
                        </p>
                        <Button asChild>
                            <a href={backup.download.url()}>
                                <Download className="size-4" />
                                Download SQL Backup
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Backup.layout = {
    breadcrumbs: [
        {
            title: 'Backup settings',
            href: backup.edit(),
        },
    ],
};
