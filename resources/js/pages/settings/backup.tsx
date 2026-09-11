import { Form, Head } from '@inertiajs/react';
import { Download, Upload } from 'lucide-react';
import { useRef } from 'react';
import BackupController from '@/actions/App/Http/Controllers/Settings/BackupController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import backup from '@/routes/backup';

export default function Backup() {
    const passwordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Backup settings" />

            <h1 className="sr-only">Backup settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Backup"
                    description="Download or restore a full SQL backup of your data"
                />

                <Card>
                    <CardContent className="space-y-4">
                        <p className="text-muted-foreground text-sm">
                            Downloads every table as a single .sql file you can
                            restore with <code>mysql &lt; backup.sql</code>.
                            Session data and password reset tokens are left out.
                        </p>
                        <Button asChild>
                            <a href={backup.download.url()}>
                                <Download className="size-4" />
                                Download SQL Backup
                            </a>
                        </Button>
                    </CardContent>
                </Card>

                <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                    <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                        <p className="font-medium">Warning</p>
                        <p className="text-sm">
                            Restoring replaces every table with the contents of
                            the uploaded file. Please proceed with caution, this
                            cannot be undone.
                        </p>
                    </div>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="destructive"
                                data-test="import-backup-button"
                            >
                                <Upload className="size-4" />
                                Restore from Backup
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Restore from SQL backup?</DialogTitle>
                            <DialogDescription>
                                Every table will be dropped and recreated from
                                the uploaded file, permanently replacing the
                                current data. Enter your password to confirm.
                            </DialogDescription>

                            <Form
                                {...BackupController.importMethod.form()}
                                options={{
                                    preserveScroll: true,
                                }}
                                encType="multipart/form-data"
                                onError={() => passwordInput.current?.focus()}
                                resetOnSuccess
                                className="space-y-6"
                            >
                                {({
                                    resetAndClearErrors,
                                    processing,
                                    errors,
                                }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="backup">
                                                SQL backup file
                                            </Label>
                                            <Input
                                                id="backup"
                                                name="backup"
                                                type="file"
                                                accept=".sql"
                                            />
                                            <InputError
                                                message={errors.backup}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="import-password"
                                                className="sr-only"
                                            >
                                                Password
                                            </Label>

                                            <PasswordInput
                                                id="import-password"
                                                name="password"
                                                ref={passwordInput}
                                                placeholder="Password"
                                                autoComplete="current-password"
                                            />

                                            <InputError
                                                message={errors.password}
                                            />
                                        </div>

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button
                                                    variant="secondary"
                                                    onClick={() =>
                                                        resetAndClearErrors()
                                                    }
                                                >
                                                    Cancel
                                                </Button>
                                            </DialogClose>

                                            <Button
                                                variant="destructive"
                                                disabled={processing}
                                                asChild
                                            >
                                                <button
                                                    type="submit"
                                                    data-test="confirm-import-backup-button"
                                                >
                                                    Restore
                                                </button>
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
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
