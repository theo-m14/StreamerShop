<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function checkIfUserAlreadyBuyProductToday(string $email, string $phone): bool
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('COUNT(o.id)')
            ->join('o.adress', 'a')
            ->join('a.contact', 'c')
            ->where('c.email = :email OR c.phone = :phone')
            ->andWhere('o.createdAt >= :startOfDay')
            ->setParameters(new ArrayCollection([
                'email' => $email,
                'phone' => $phone,
                'startOfDay' => new \DateTimeImmutable('today')
            ]));

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

//    /**
//     * @return Order[] Returns an array of Order objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Order
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
