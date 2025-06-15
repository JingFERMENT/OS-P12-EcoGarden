<?php

namespace App\Service;

class MonthService
{
    private const MONTHS = [
        '1' => 'janvier',
        '2' => 'février',
        '3' => 'mars',
        '4' => 'avril',
        '5' => 'mai',
        '6' => 'juin',
        '7' => 'juillet',
        '8' => 'août',
        '9' => 'septembre',
        '10' => 'octobre',
        '11' => 'novembre',
        '12' => 'décembre',
    ];

    public function getMonthName(string $month): ?string
    {
        return self::MONTHS[$month] ?? null;
    }

    public function getCurrentMonthName(): string
    {
        $currentMonth = (new \DateTime())->format('n');
        return self::MONTHS[$currentMonth];
    }

}
