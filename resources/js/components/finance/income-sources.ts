import type { IncomeSource } from '@/types/finance';

export const INCOME_SOURCE_LABELS: Record<IncomeSource, string> = {
    salary: 'Salary',
    freelance: 'Freelance',
    business: 'Business',
    bonus: 'Bonus',
    other: 'Other',
};

export const INCOME_SOURCE_OPTIONS = (
    Object.entries(INCOME_SOURCE_LABELS) as [IncomeSource, string][]
).map(([id, label]) => ({ id, label }));
