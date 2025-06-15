<?php

namespace App\Repository;

use App\Entity\Advice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Advice>
 */
class AdviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advice::class);
    }
    
    /**
     * Finds all Advice entities with pagination for a specific month.
     * @param int $page The page number to retrieve
     * @param int $limit The number of items per page
     * @param string $months The name of the months to filter by
     * @return Advice[] Returns an array of Advice objects
     */
    public function findWithPaginationByMonth(int $page, int $limit, string $months): array
    {
        return $this->createQueryBuilder('a')
        ->join('a.months', 'm')
            ->andWhere('m.name = :monthName')
            ->setParameter('monthName', $months)
            ->setFirstResult(($page - 1) * $limit) //à partir de quand nous allons récupérer les livres ; 
            ->setMaxResults($limit) //nombre de livres à récupérer
            ->getQuery()
            ->getResult();
    }
}
