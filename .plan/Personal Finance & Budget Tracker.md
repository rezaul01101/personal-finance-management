# Personal Finance & Budget Tracker
## Product Specification & AI Development Plan

---

# 1. Product Overview

Build a clean, minimal, mobile-first personal finance management application.

The application is primarily designed for personal use.

The main purpose is NOT to show income prominently.

The main purpose is to help the user answer these questions immediately:

- How much budget do I have for each purpose?
- How much have I already spent?
- How much is remaining?
- How many days are left in the current month?
- Based on the remaining budget and remaining days, how much can I safely spend per day?
- Where is my money being spent?
- How much money have I saved?
- How much money have I lent to others?
- How much money have others returned?
- How much money do others still owe me?
- How much money have I borrowed from others?
- How much do I still owe?

The application should feel like a combination of:

- Personal finance manager
- Budget tracker
- Expense tracker
- Savings tracker
- Loan/lending tracker

The UI must be clean, minimal, modern, responsive, and mobile-first.

---

# 2. Core Design Philosophy

The application should NOT feel like a complicated accounting application.

The user should be able to:

1. Open the application.
2. Immediately understand the current month's budget situation.
3. Add an expense within a few seconds.
4. Quickly see whether each budget is healthy or exceeded.
5. Open any budget category and understand exactly where the money was spent.

Prioritize:

- Simplicity
- Readability
- Fast data entry
- Minimal UI
- Clear financial information
- Mobile usability
- Responsive desktop usability

Avoid:

- Too many cards
- Too many colors
- Large unnecessary charts
- Complicated accounting terminology
- Excessive animations
- Information overload

---

# 3. Main Navigation

Mobile navigation should be simple.

Primary navigation:

- Home
- Transactions
- Add Expense (+)
- Income
- Savings
- Loans

If too many navigation items make the mobile UI crowded, use a More menu for less frequently used sections.

The Add Expense button should be visually prominent.

Desktop should use a sidebar navigation.

Desktop navigation:

- Home
- Transactions
- Income
- Budgets
- Savings
- Loans
- Accounts
- Reports
- Settings

---

# 4. Home Screen

## Purpose

The Home screen is the most important screen.

DO NOT prominently display total income.

The Home screen should focus on monthly budget health.

At the top:

```text
August 2026

2 days left
```

The month selector should allow the user to move between months.

Example:

```text
‹    August 2026    ›
```

---

# 5. Budget Category Cards

The Home screen should display the user's main budget categories.

Example:

```text
Family

৳12,400 used
of ৳15,000

████████████░░ 83%

Available       ৳2,600
Daily Safe Spend ৳1,300/day
```

Another example:

```text
Personal

৳7,200 used
of ৳10,000

██████████░░░░ 72%

Available       ৳2,800
Daily Safe Spend ৳1,400/day
```

Another:

```text
Lending

৳3,000 used
of ৳5,000

████████░░░░░░ 60%

Available       ৳2,000
Daily Safe Spend ৳1,000/day
```

Each budget category should be clickable.

Clicking a category opens the Category Details page.

---

# 6. Budget Calculation

For each budget category calculate:

```text
budget_amount
used_amount
available_amount
remaining_days
daily_safe_spend
usage_percentage
```

Formula:

```text
available_amount =
budget_amount - used_amount
```

Remaining days should be calculated based on the current selected month.

Example:

```text
Budget = ৳15,000
Used = ৳12,400

Available = ৳2,600

Days remaining = 2

Daily Safe Spend =
৳2,600 / 2
= ৳1,300
```

If there are no remaining days:

```text
Daily Safe Spend = ৳0
```

If the budget is exceeded:

```text
Budget = ৳15,000
Used = ৳15,800

Available = -৳800
```

Display:

```text
Budget exceeded

৳800 over budget
```

Do not show negative daily spending as a normal value.

---

# 7. Budget Health

Budget cards should visually communicate health.

Suggested states:

### Healthy

Budget usage is below a safe threshold.

Display a normal progress bar.

### Warning

Budget usage is getting high.

Display a warning state.

### Exceeded

Used amount is greater than budget.

Display:

```text
Budget exceeded
৳800 over budget
```

The UI should remain minimal.

Do not use aggressive colors everywhere.

Use color only where it communicates status.

---

# 8. Budget Categories

The application must support multiple user-defined budget categories.

Example:

```text
Family
Personal
Lending
Education
Travel
Business
Other
```

The user can create, edit, archive, or delete categories where appropriate.

A category should have:

```text
name
icon
description
status
```

Categories should be reusable across months.

---

# 9. Monthly Budget

A user should be able to define a budget for each category for a specific month.

Example:

```text
August 2026

Family       ৳15,000
Personal     ৳10,000
Lending       ৳5,000
```

Budget should be month-specific.

The system should support different budget amounts in different months.

Example:

```text
July:
Family = ৳12,000

August:
Family = ৳15,000

September:
Family = ৳18,000
```

Do not assume the same budget every month unless the user explicitly enables recurring budgets.

---

# 10. Category Details Page

When a user clicks a budget category, open a detailed page.

Example:

```text
Family
August 2026

৳12,400 used
of ৳15,000

████████████░░ 83%

Available
৳2,600

Daily Safe Spend
৳1,300/day
```

Then show:

## Summary

Break down spending inside the category.

Example:

```text
Grocery        ৳5,200
Bills          ৳3,200
Medicine       ৳1,500
Other          ৳2,500
```

The summary can optionally include percentage.

Example:

```text
Grocery
৳5,200
42%
```

Keep the visualization subtle.

---

# 11. Category Transactions

Below the summary show transactions belonging to that budget category.

Group transactions by date.

Example:

```text
Today

Grocery              -৳2,500
Rice, oil & vegetables

Electricity           -৳1,200

28 August

Medicine                -৳800
```

Each transaction should be clickable.

The transaction details page should show:

- Amount
- Category
- Budget
- Account
- Date
- Note
- Receipt/image if available
- Created time

---

# 12. Add Expense

Adding an expense is the most frequently used action.

The flow must be extremely fast.

Mobile UI:

```text
Add Expense

Amount

৳ 350

Category
Food

Budget
Personal

Account
bKash

Date
30 Aug 2026

Receipt
[ Add Receipt ]

Note
Lunch

[ Save Expense ]
```

Amount should be the primary visual element.

Do not force the user to upload a receipt.

Receipt/image is optional.

---

# 13. Expense Categories

Expense categories are different from budget groups.

Example expense categories:

```text
Food
Grocery
Transport
Shopping
Medicine
Electricity
Internet
Mobile
Entertainment
Education
Other
```

An expense belongs to:

- One expense category
- One budget category
- One account

Example:

```text
Expense:
৳2,500

Expense Category:
Grocery

Budget:
Family

Account:
bKash
```

---

# 14. Receipt / Image

An expense can optionally have one or more images.

Possible images:

- Receipt
- Bill
- Product photo
- Screenshot
- Other supporting image

The user should be able to:

- Take a photo using mobile camera
- Select an image from gallery
- Remove an image
- View the image later

Uploading images must never be mandatory.

The UI should make image attachment optional.

---

# 15. Expense Editing

Users should be able to edit:

- Amount
- Category
- Budget
- Account
- Date
- Note
- Attachments

When an expense is edited, all budget calculations must update automatically.

Example:

```text
Old:
Family expense = ৳1,000

New:
Family expense = ৳1,500
```

Family budget usage should immediately increase by ৳500.

---

# 16. Expense Deletion

Deleting an expense must update:

- Budget used amount
- Available budget
- Daily Safe Spend
- Account balance
- Reports

Use a confirmation step before permanent deletion.

---

# 17. Transactions

The Transactions screen shows all financial transactions.

Filters:

- All
- Expense
- Income
- Savings
- Loan Given
- Loan Repayment
- Loan Taken
- Loan Returned

Support:

- Search
- Date filter
- Month filter
- Category filter
- Budget filter
- Account filter
- Transaction type filter

Transactions should be grouped by date where useful.

Example:

```text
Today

Grocery          -৳2,500
Food               -৳350
Transport          -৳200

Yesterday

Electricity      -৳1,200
```

---

# 18. Income

Income must be tracked but should NOT dominate the Home screen.

Income can include:

```text
Salary
Freelance
Business
Bonus
Other
```

Income fields:

```text
amount
source
date
account
note
attachment
```

Example:

```text
Salary
৳45,000
30 August 2026
Bank
```

Income should affect account balance.

Income should not automatically become an expense budget.

---

# 19. Accounts

The application should track where money physically exists.

Examples:

```text
Cash
bKash
Nagad
Bank
Credit Card
Other
```

Each account should have:

```text
name
type
balance
status
```

Transactions should affect account balance.

Example:

```text
bKash balance:
৳12,500

Expense:
৳500

New balance:
৳12,000
```

---

# 20. Account Transfer

The application should support transferring money between accounts.

Example:

```text
Bank
৳50,000

Transfer ৳10,000

Bank
৳40,000

bKash
+৳10,000
```

A transfer should NOT be treated as income or expense.

This is important for accurate financial reporting.

---

# 21. Savings

Savings should be goal-based.

Example:

```text
Emergency Fund

৳85,000 / ৳200,000

████████░░░░░░░░ 42%

Target:
৳200,000
```

Another:

```text
Travel

৳20,000 / ৳50,000

██████░░░░░░░░░░ 40%
```

Savings goal fields:

```text
name
target_amount
target_date
description
status
```

---

# 22. Savings Transactions

A savings goal can receive multiple contributions.

Example:

```text
Emergency Fund

10 August
+৳5,000

20 August
+৳5,000

30 August
+৳5,000
```

Total:

```text
+৳15,000 this month
```

The system should maintain complete savings history.

---

# 23. Savings Withdrawal

Allow users to withdraw money from a savings goal.

Example:

```text
Emergency Fund

Current:
৳100,000

Withdraw:
৳10,000

New:
৳90,000
```

Withdrawal must be recorded as a separate savings transaction.

Do not delete the original saving transaction.

---

# 24. Loan / Lending

The application must support money given to other people.

Example:

```text
Karim

Given:
৳10,000

Returned:
৳4,000

Outstanding:
৳6,000
```

Loan fields:

```text
person_name
amount
given_date
expected_return_date
note
status
```

---

# 25. Loan Repayment

A person can return money partially or fully.

Example:

```text
Original:
৳10,000

Repayment 1:
৳2,000

Repayment 2:
৳2,000

Remaining:
৳6,000
```

Never overwrite the original loan amount.

Maintain a transaction/history record.

---

# 26. Loan History

Loan details should show:

```text
Karim

Total Given
৳10,000

Returned
৳4,000

Outstanding
৳6,000

History

10 Aug
Given             ৳10,000

20 Aug
Returned           ৳2,000

28 Aug
Returned           ৳2,000
```

---

# 27. Loan Taken

The user may also borrow money from someone else.

The application should separately track:

```text
Loan Given
Loan Taken
```

Example:

```text
Loan Taken

Rahim

Borrowed:
৳20,000

Paid:
৳5,000

Outstanding:
৳15,000
```

Loan Taken should not be mixed with Loan Given.

---

# 28. Loan Given vs Expense

IMPORTANT:

Money given as a loan is NOT an expense.

Example:

```text
Give Karim ৳10,000

Cash decreases:
৳10,000

But:
Expense = ৳0

Loan Receivable = ৳10,000
```

When Karim returns ৳10,000:

```text
Cash increases:
৳10,000

Loan Receivable:
৳10,000 → ৳0
```

The system must preserve this distinction.

---

# 29. Lending Budget

The user may optionally define a monthly lending budget.

Example:

```text
Lending Budget
৳5,000
```

If the user gives:

```text
Karim
৳3,000
```

then:

```text
Lending Budget

৳3,000 / ৳5,000
60%

Available:
৳2,000
```

However, the actual loan amount must remain a loan/receivable and must not be permanently classified as an expense.

---

# 30. Dashboard Summary

The Home screen should NOT show every financial metric.

Primary focus:

```text
Month
Days remaining

Family Budget
Personal Budget
Other Budgets
```

Each category should show:

```text
Used
Budget
Available
Daily Safe Spend
Progress
```

Optional secondary information:

```text
Savings progress
Outstanding loans
```

But keep the main screen uncluttered.

---

# 31. Reports

Reports should be available separately.

Possible reports:

## Monthly Expense

```text
August 2026

Family       ৳12,400
Personal      ৳7,200
Other         ৳2,000
```

## Category Spending

```text
Food          ৳4,500
Grocery       ৳5,200
Transport     ৳2,200
Bills         ৳5,000
```

## Budget Performance

```text
Family
Budget: ৳15,000
Spent:  ৳12,400
Remaining: ৳2,600
```

## Savings

```text
Total Saved
৳100,000
```

## Loans

```text
Money Given
৳25,000

Money Returned
৳8,000

Outstanding
৳17,000
```

Reports should prioritize readability over complex charts.

---

# 32. Month Navigation

The user should be able to navigate months.

Example:

```text
‹   August 2026   ›
```

Selecting another month should update:

- Budgets
- Expenses
- Spending
- Remaining days
- Daily Safe Spend
- Summary

The current month should be the default.

---

# 33. Budget Creation Experience

Creating a monthly budget should be simple.

Example:

```text
August 2026

Family
৳15,000

Personal
৳10,000

Lending
৳5,000

[ Save Budget ]
```

Allow the user to add additional budget categories.

---

# 34. Budget Rollover

Do NOT automatically roll unused budget into the next month unless explicitly enabled.

Example:

August:

```text
Budget:
৳15,000

Used:
৳12,000

Unused:
৳3,000
```

September should normally start with its own defined budget.

Future enhancement:

```text
Enable budget rollover
```

---

# 35. Recurring Budget

Optional future feature.

Allow users to mark a budget as recurring.

Example:

```text
Family
৳15,000
Every month
```

The system can automatically create the next month's budget.

---

# 36. Recurring Expenses

Optional future feature.

Examples:

```text
Internet
৳1,000 / month

Mobile
৳500 / month

Rent
৳5,000 / month
```

The system can create recurring transactions automatically.

---

# 37. Notifications / Alerts

Optional but recommended.

Budget alert:

```text
Family budget is 90% used.
```

Budget exceeded:

```text
Family budget exceeded by ৳800.
```

Loan reminder:

```text
Karim's repayment date is tomorrow.
```

Savings milestone:

```text
Emergency Fund reached 50%.
```

---

# 38. Mobile UI Principles

Mobile UI must be optimized for one-handed usage.

Use:

- Large touch targets
- Large amount display
- Simple forms
- Bottom navigation
- Floating/central Add button
- Minimal cards
- Clear typography
- Short labels

Avoid:

- Dense tables
- Tiny text
- Too many filters visible at once
- Long forms
- Too many icons

---

# 39. Mobile Home Layout

Recommended layout:

```text
August 2026

2 days left

────────────────────

Family

৳12,400 used
of ৳15,000

████████████░░ 83%

Available
৳2,600

Daily Safe Spend
৳1,300/day

────────────────────

Personal

৳7,200 used
of ৳10,000

██████████░░░░ 72%

Available
৳2,800

Daily Safe Spend
৳1,400/day

────────────────────

Lending

৳3,000 used
of ৳5,000

████████░░░░░░ 60%

Available
৳2,000

Daily Safe Spend
৳1,000/day
```

Cards should be compact.

---

# 40. Mobile Add Expense Layout

```text
Add Expense

Amount

৳ 350

Category
Food

Budget
Personal

Account
bKash

Date
30 Aug 2026

Receipt
📷 Add Receipt

Note
Lunch

[ Save Expense ]
```

The amount field should receive focus quickly.

---

# 41. Desktop UI

Desktop should use a left sidebar.

Example:

```text
My Finance

Dashboard

Transactions
Income

Budgets
Savings

Loans
Accounts

Reports

Settings
```

Main content should have generous whitespace.

Desktop dashboard:

```text
August 2026                         2 days left

──────────────────────────────────────────────

Family
৳12,400 / ৳15,000
████████████░░
Available ৳2,600
Daily Safe Spend ৳1,300

Personal
৳7,200 / ৳10,000
██████████░░░░
Available ৳2,800
Daily Safe Spend ৳1,400

──────────────────────────────────────────────

Recent Transactions
```

Do not fill the desktop screen with unnecessary widgets.

---

# 42. Design System

The visual style should be:

- Minimal
- Modern
- Calm
- Professional
- Clean

Prefer:

- Light background
- White content areas
- Subtle borders
- Small radius
- Soft shadows only where necessary
- One primary accent color
- Neutral text colors

Do not use many bright colors.

Status colors can be used sparingly:

- Normal
- Warning
- Exceeded
- Positive

---

# 43. Empty States

Every module should have a useful empty state.

Example:

```text
No expenses yet.

Start tracking your spending.

[ + Add Expense ]
```

Savings:

```text
No savings goals yet.

Create your first savings goal.

[ Create Goal ]
```

Loans:

```text
No active loans.

[ Add Loan ]
```

Do not show empty tables without explanation.

---

# 44. Validation

Financial forms must have strong validation.

Examples:

- Amount must be greater than zero.
- Date must be valid.
- Budget must exist before assigning an expense.
- Account must exist before creating a financial transaction.
- Repayment cannot exceed outstanding loan amount.
- Savings withdrawal cannot exceed current saved amount.
- Required fields must be clearly marked.

---

# 45. Financial Accuracy

Financial calculations must be centralized.

Do NOT duplicate financial calculations in multiple controllers/views.

Examples of calculations that should have a single source of truth:

```text
Budget used
Budget remaining
Daily Safe Spend
Loan outstanding
Savings balance
Account balance
```

Whenever a transaction changes, all related calculations must remain consistent.

Avoid floating-point errors for money.

All money calculations should use precise decimal handling.

---

# 46. Transaction Integrity

Whenever a financial transaction is created, edited, or deleted:

Update all related financial values consistently.

Example:

Expense:

```text
Expense +৳500
↓
Account balance -৳500
↓
Budget used +৳500
```

Expense deleted:

```text
Expense -৳500
↓
Account balance +৳500
↓
Budget used -৳500
```

All related operations should be atomic.

---

# 47. Authorization

Users must only be able to access their own financial data.

A user must never be able to view or modify:

- Another user's expenses
- Another user's budgets
- Another user's accounts
- Another user's savings
- Another user's loans
- Another user's income

Apply authorization consistently.

---

# 48. Search & Filtering

Transactions should support:

```text
Search
Date
Month
Amount
Category
Budget
Account
Transaction Type
```

Mobile filters should open in a bottom sheet or modal instead of displaying all filters permanently.

Desktop can display filters horizontally.

---

# 49. Performance

The application should remain fast even with thousands of transactions.

Use:

- Pagination
- Proper database indexes
- Lazy loading where appropriate
- Optimized queries
- Avoid N+1 queries
- Efficient image handling

Do not load all historical transactions on the Home screen.

Only load what is required.

---

# 50. Image Handling

Uploaded receipt images should:

- Be validated
- Have reasonable size limits
- Be compressed/optimized where appropriate
- Have safe filenames
- Store metadata separately
- Be deletable
- Be viewable from transaction details

The application should work normally even when no image is attached.

---

# 51. Responsive Behavior

The application must support:

### Mobile

```text
320px+
```

### Tablet

Responsive intermediate layout.

### Desktop

Sidebar + main content.

Do not simply shrink desktop UI for mobile.

Mobile and desktop should have intentionally designed layouts.

---

# 52. Progressive Web App Experience

The application should behave like a mobile application when installed.

Support:

- Installable application
- App icon
- Standalone mode
- Responsive layout
- Mobile-friendly navigation

Offline functionality can be implemented as a future phase.

Do not block the MVP because of offline support.

---

# 53. Future Offline Mode

Future enhancement:

When there is no internet:

```text
Add Expense
        ↓
Local temporary storage
        ↓
Internet restored
        ↓
Sync with server
```

This should be designed later.

Do not implement complex offline synchronization in the first version unless necessary.

---

# 54. Suggested Development Phases

## Phase 1 — Foundation

Implement:

- Authentication
- Main layout
- Navigation
- User settings
- Budget categories
- Expense categories
- Accounts

---

## Phase 2 — Budget & Expense

Implement:

- Monthly budgets
- Expense creation
- Expense editing
- Expense deletion
- Expense list
- Receipt upload
- Budget calculation
- Daily Safe Spend
- Budget health

---

## Phase 3 — Dashboard

Implement:

- Monthly navigation
- Days remaining
- Budget cards
- Progress bars
- Budget details
- Spending summaries
- Category transactions

---

## Phase 4 — Income

Implement:

- Income creation
- Income editing
- Income deletion
- Income history
- Account balance updates

---

## Phase 5 — Savings

Implement:

- Savings goals
- Contributions
- Withdrawals
- Progress
- Savings history

---

## Phase 6 — Loans

Implement:

- Loan Given
- Loan Taken
- Repayments
- Loan history
- Outstanding balance
- Due dates
- Lending budget integration

---

## Phase 7 — Reports

Implement:

- Monthly report
- Category report
- Budget report
- Savings report
- Loan report
- Transaction report

---

## Phase 8 — Improvements

Implement:

- Recurring budgets
- Recurring expenses
- Notifications
- Budget alerts
- Loan reminders
- Better image handling
- PWA improvements
- Offline mode

---

# 55. Important Business Rules

The AI agent must follow these rules strictly.

### Rule 1

Income is not an expense.

### Rule 2

Savings are not expenses.

### Rule 3

Money given as a loan is not an expense.

### Rule 4

Loan repayment received is not normal income.

It is a reduction of receivable.

### Rule 5

Money borrowed is not normal income.

It is a liability.

### Rule 6

Money transferred between accounts is neither income nor expense.

### Rule 7

Expense must affect the selected account balance.

### Rule 8

Expense must affect the selected budget.

### Rule 9

Editing/deleting a transaction must reverse and reapply all related calculations correctly.

### Rule 10

The Home screen should prioritize budget health instead of income.

---

# 56. Example User Scenario

User has:

```text
Family Budget:
৳15,000

Personal Budget:
৳10,000

Lending Budget:
৳5,000

Savings Target:
৳15,000/month
```

During the month:

```text
Family Grocery:
৳5,000

Family Electricity:
৳1,500

Personal Food:
৳3,000

Personal Transport:
৳1,500

Loan Given to Karim:
৳3,000

Savings:
৳15,000
```

Home should show:

```text
Family

৳6,500 / ৳15,000

Available:
৳8,500

Daily Safe Spend:
based on remaining days
```

Personal:

```text
৳4,500 / ৳10,000

Available:
৳5,500
```

Lending:

```text
৳3,000 / ৳5,000

Available:
৳2,000
```

Savings:

```text
৳15,000 saved this month
```

Karim:

```text
৳3,000 outstanding
```

---

# 57. AI Agent Development Rules

When implementing this project:

1. First understand the entire specification.
2. Do not implement everything in one huge change.
3. Break development into small logical phases.
4. Before changing the database, understand existing relationships.
5. Do not unnecessarily rewrite existing working code.
6. Follow existing project conventions.
7. Keep business logic separate from UI.
8. Reuse components where appropriate.
9. Keep financial calculations centralized.
10. Add validation for every financial operation.
11. Use database transactions for multi-step financial operations.
12. Protect user data with proper authorization.
13. Test calculations with realistic examples.
14. Test edge cases.
15. Do not introduce unnecessary dependencies.
16. Keep mobile UX as the primary design target.
17. Make desktop responsive rather than creating a separate application.
18. Do not add features that are not required for the current phase without discussing their impact.

---

# 58. Testing Requirements

Test at least these cases.

## Budget

```text
Budget = 15,000
Expense = 0
Expense = 10,000
Expense = 15,000
Expense = 15,001
```

## Daily Safe Spend

```text
Available = 10,000
Days = 10
Result = 1,000/day
```

```text
Available = 2,600
Days = 2
Result = 1,300/day
```

```text
Days = 0
Result = 0/day
```

## Loan

```text
Loan = 10,000
Repayment = 4,000
Outstanding = 6,000
```

Do not allow:

```text
Repayment = 11,000
```

## Savings

```text
Saved = 100,000
Withdraw = 20,000
Remaining = 80,000
```

Do not allow withdrawal above available savings.

## Account

```text
Balance = 10,000
Expense = 2,000

New balance = 8,000
```

---

# 59. MVP Definition

The first usable version should contain only:

### Home

- Monthly budget categories
- Used amount
- Available amount
- Daily Safe Spend
- Remaining days
- Progress bar

### Expenses

- Add
- Edit
- Delete
- List
- Category
- Budget
- Account
- Receipt
- Note

### Transactions

- Expense
- Income
- Savings
- Loans

### Income

- Add
- Edit
- Delete
- History

### Savings

- Goal
- Add saving
- Withdraw
- Progress

### Loans

- Given
- Taken
- Repayment
- Outstanding

### Accounts

- Cash
- Bank
- Mobile wallet
- Balance

Do not add advanced automation before this core flow is stable.

---

# 60. Final Product Goal

The final product should feel extremely simple.

When the user opens the application, the first thing they should understand is:

```text
How much budget did I have?

How much did I use?

How much is left?

How much can I safely spend per day?

Where did I spend it?
```

The application should help the user control money rather than simply record money.

The overall experience should be:

```text
Open App
    ↓
See Budget Health
    ↓
See Available Money
    ↓
See Daily Safe Spend
    ↓
Add Expense Quickly
    ↓
Review Transactions
    ↓
Track Savings
    ↓
Track Loans
```

Keep the entire product minimal, fast, understandable, and practical for daily personal use.