<?php

namespace App\Enums;

enum IncomeSource: string
{
    case Salary = 'salary';
    case Freelance = 'freelance';
    case Business = 'business';
    case Bonus = 'bonus';
    case Other = 'other';
}
