export type AccountType = 'cash' | 'bank' | 'mobile_wallet' | 'card' | 'other';

export type CategoryStatus = 'active' | 'archived';

export type IncomeSource =
    | 'salary'
    | 'freelance'
    | 'business'
    | 'bonus'
    | 'other';

export type SavingsGoalStatus = 'active' | 'completed' | 'archived';

export type SavingsTransactionType = 'contribution' | 'withdrawal';

export interface Account {
    id: number;
    name: string;
    type: AccountType;
    balance: string;
    status: CategoryStatus;
}

export interface Contact {
    id: number;
    name: string;
    loans_count?: number;
}

export interface BudgetCategory {
    id: number;
    name: string;
    icon: string | null;
    description: string | null;
    status: CategoryStatus;
    sort_order: number;
}

export interface ExpenseCategory {
    id: number;
    name: string;
    icon: string | null;
    status: CategoryStatus;
    sort_order: number;
}

export interface ExpenseAttachment {
    id: number;
    original_filename: string | null;
    url: string;
}

export interface Expense {
    id: number;
    expense_category_id: number;
    budget_category_id: number;
    account_id: number;
    amount: string;
    spent_on: string;
    note: string | null;
    expense_category?: ExpenseCategory;
    budget_category?: BudgetCategory;
    account?: Account;
    attachments?: ExpenseAttachment[];
}

export interface Income {
    id: number;
    account_id: number;
    source: IncomeSource;
    amount: string;
    received_on: string;
    note: string | null;
    account?: Account;
}

export interface AccountTransfer {
    id: number;
    from_account_id: number;
    to_account_id: number;
    amount: string;
    transferred_on: string;
    note: string | null;
    from_account?: Account;
    to_account?: Account;
}

export interface BudgetSummary {
    budget_category_id: number;
    budget_amount: string;
    used_amount: string;
    available_amount: string;
    is_exceeded: boolean;
    over_budget_amount: string;
    remaining_days: number;
    daily_safe_spend: string;
    usage_percentage: number;
}

export interface SavingsGoal {
    id: number;
    name: string;
    target_amount: string;
    target_date: string | null;
    description: string | null;
    status: SavingsGoalStatus;
}

export interface SavingsSummary {
    savings_goal_id: number;
    saved_amount: string;
    target_amount: string;
    remaining_amount: string;
    usage_percentage: number;
}

export interface SavingsTransaction {
    id: number;
    savings_goal_id: number;
    account_id: number;
    type: SavingsTransactionType;
    amount: string;
    transacted_on: string;
    note: string | null;
    account?: Account;
}

export type LoanType = 'given' | 'taken';

export interface Loan {
    id: number;
    account_id: number;
    contact_id: number;
    type: LoanType;
    amount: string;
    loan_date: string;
    expected_return_date: string | null;
    note: string | null;
    account?: Account;
    contact: Contact;
    attachments?: LoanAttachment[];
}

export interface LoanAttachment {
    id: number;
    original_filename: string | null;
    url: string;
}

export interface LoanRepayment {
    id: number;
    loan_id: number;
    account_id: number | null;
    amount: string;
    repaid_on: string;
    note: string | null;
    account?: Account | null;
}

export interface LoanTransfer {
    id: number;
    loan_id: number;
    account_id: number;
    amount: string;
    transferred_on: string;
    note: string | null;
    account?: Account;
}

export interface LoanProgress {
    loan_id: number;
    total_repaid: string;
    outstanding: string;
    total_transferred: string;
    held_balance: string;
}

export interface ContactLoanSummary {
    total_amount: string;
    outstanding: string;
}

export interface LoanSummary {
    total_given: string;
    total_returned_by_borrowers: string;
    outstanding_receivable: string;
    total_taken: string;
    total_paid_to_lenders: string;
    outstanding_payable: string;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}
