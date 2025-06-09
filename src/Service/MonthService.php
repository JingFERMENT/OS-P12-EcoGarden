<?php

namespace App\Service;

class MonthService
{
    private const MONTHS = [
        '01' => 'janvier',
        '02' => 'février',
        '03' => 'mars',
        '04' => 'avril',
        '05' => 'mai',
        '06' => 'juin',
        '07' => 'juillet',
        '08' => 'août',
        '09' => 'septembre',
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
        $currentMonth = (new \DateTime())->format('m');
        return self::MONTHS[$currentMonth];
    }

}
