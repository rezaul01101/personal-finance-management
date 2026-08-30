import {
    CreditCard,
    Landmark,
    Smartphone,
    Wallet,
    type LucideIcon,
} from 'lucide-react';
import type { AccountType } from '@/types/finance';

export const ACCOUNT_TYPE_ICONS: Record<AccountType, LucideIcon> = {
    cash: Wallet,
    bank: Landmark,
    mobile_wallet: Smartphone,
    card: CreditCard,
    other: Wallet,
};
