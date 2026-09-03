import { Download } from 'lucide-react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { usePwaInstall } from '@/hooks/use-pwa-install';

type Props = Omit<ComponentProps<typeof Button>, 'onClick' | 'children'>;

export default function InstallAppButton(props: Props) {
    const { isInstallable, isInstalled, install } = usePwaInstall();

    if (!isInstallable || isInstalled) {
        return null;
    }

    return (
        <Button {...props} onClick={() => void install()}>
            <Download />
            Download App
        </Button>
    );
}
