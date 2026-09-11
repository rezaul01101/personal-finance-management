<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Noto Sans Bengali', sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 12px;
        }

        .filters {
            margin-bottom: 12px;
            font-size: 10px;
            color: #374151;
        }

        .filters span {
            display: inline-block;
            margin-right: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        td.amount, th.amount {
            text-align: right;
        }

        tr.date-group td {
            background-color: #f3f4f6;
            font-weight: bold;
            border-bottom: 1px solid #d1d5db;
        }

        tfoot td {
            font-weight: bold;
            border-top: 2px solid #1f2937;
            border-bottom: none;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    @if (count($filterSummary))
        <p class="filters">
            @foreach ($filterSummary as $line)
                <span>{{ $line }}</span>
            @endforeach
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Description</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        @forelse ($groupedExpenses as $date => $expensesForDate)
            <tbody>
                <tr class="date-group">
                    <td colspan="2">{{ date('D, d M Y', strtotime($date)) }}</td>
                    <td class="amount">৳{{ number_format((float) $expensesForDate->sum(fn ($expense) => (float) $expense->amount), 2) }}</td>
                </tr>
                @foreach ($expensesForDate as $expense)
                    <tr>
                        <td>{{ $expense->expenseCategory?->name }}</td>
                        <td>{{ $expense->note }}</td>
                        <td class="amount">৳{{ number_format((float) $expense->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        @empty
            <tbody>
                <tr>
                    <td colspan="3">No expenses match the selected filters.</td>
                </tr>
            </tbody>
        @endforelse
        @if ($groupedExpenses->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td class="amount">৳{{ number_format((float) $total, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
