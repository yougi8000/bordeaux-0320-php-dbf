<?php

namespace App\Repository;

use App\Data\SearchData;
use App\Entity\Call;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use \DateTime;
use \DateInterval;

/**
 * @method Call|null find($id, $lockMode = null, $lockVersion = null)
 * @method Call|null findOneBy(array $criteria, array $orderBy = null)
 * @method Call[]    findAll()
 * @method Call[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CallRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Call::class);
    }


    public function callsOnTheWayForClient($clientId)
    {
        $date = new DateTime('now');
        $dateLimit = $date->sub(new DateInterval('P7D'));

        return $this->createQueryBuilder('c')
            ->Where('c.client = :client')
            ->setParameter('client', $clientId)
            ->andWhere('c.createdAt >= :limitDate')
            ->setParameter('limitDate', $dateLimit)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }
    public function findCallsAddedToday($author)
    {
        $qb = $this->createQueryBuilder('c')
            ->Where('c.author = :author')
            ->setParameter('author', $author)
            ->andWhere('c.createdAt >= CURRENT_DATE()')
            ->orderBy('c.createdAt', 'DESC')
            ->join('c.client', 'cl')->addSelect('cl')
            ->join('cl.civility', 'civ')->addSelect('civ')
            ->join('c.service', 's')->addSelect('s')
            ->join('s.concession', 'concession')->addSelect('concession')
            ->join('c.recallPeriod', 'rp')->addSelect('rp')
            ->join('c.comment', 'co')->addSelect('co')
            ->join('c.subject', 'subj')->addSelect('subj')
            ->join('c.vehicle', 'v')->addSelect('v')
            ->setMaxResults(50)
            ->getQuery();

        return $qb->execute();
    }


    public function callsToProcessByUser($recipient)
    {

        return $this->createQueryBuilder('c')
            ->Where('c.recipient = :recipient')
            ->setParameter('recipient', $recipient)
            ->innerJoin('c.recipient', 'r')
            ->addSelect('r')
            ->innerJoin('c.recallPeriod', 'rp')
            ->addSelect('rp')
            ->innerJoin('c.subject', 's')
            ->addSelect('s')
            ->innerJoin('c.comment', 'co')
            ->addSelect('co')
            ->andWhere('c.isProcessEnded IS NULL')
            ->andWhere('c.isProcessed IS NULL')
            ->orderBy('c.isUrgent', 'DESC')
            ->addOrderBy('c.recallDate, c.recallHour', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function lastCallToProcessByUser($recipient)
    {

        return $this->createQueryBuilder('c')
            ->Where('c.recipient = :recipient')
            ->setParameter('recipient', $recipient)
            ->innerJoin('c.recipient', 'r')
            ->addSelect('r')
            ->innerJoin('c.recallPeriod', 'rp')
            ->addSelect('rp')
            ->innerJoin('c.subject', 's')
            ->addSelect('s')
            ->innerJoin('c.comment', 'co')
            ->addSelect('co')
            ->andWhere('c.isProcessEnded IS NULL')
            ->andWhere('c.isProcessed IS NULL')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function callsInProcessByUser($recipient)
    {
        return $this->createQueryBuilder('c')
            ->Where('c.recipient = :recipient')
            ->setParameter('recipient', $recipient)
            ->innerJoin('c.recipient', 'r')
            ->addSelect('r')
            ->innerJoin('c.recallPeriod', 'rp')
            ->addSelect('rp')
            ->innerJoin('c.subject', 's')
            ->addSelect('s')
            ->innerJoin('c.comment', 'co')
            ->addSelect('co')
            ->andWhere('c.isProcessEnded IS NULL')
            ->andWhere('c.isProcessed IS NOT NULL')
            ->orderBy('c.recallDate, c.recallHour', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }


    public function getNewCallsForUser($recipient, $lastId)
    {
        return $this->createQueryBuilder('c')
            ->Where('c.recipient = :recipient')
            ->setParameter('recipient', $recipient)
            ->innerJoin('c.recipient', 'r')
            ->addSelect('r')
            ->innerJoin('c.recallPeriod', 'rp')
            ->addSelect('rp')
            ->innerJoin('c.subject', 's')
            ->addSelect('s')
            ->innerJoin('c.comment', 'co')
            ->addSelect('co')
            ->andWhere('c.isProcessEnded IS NULL')
            ->andWhere('c.isProcessed IS NULL')
            ->andWhere('c.id > :sessionCall')
            ->setParameter('sessionCall', $lastId)
            ->orderBy('c.recallDate', 'DESC')
            ->addOrderBy('c.recallHour', 'DESC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findSearch(SearchData $searchData): array
    {
        $query = $this->createQueryBuilder('c')
            ->join('c.client', 'cl')->addSelect('cl')
            ->orWhere('cl.phone LIKE :phone')->setParameter('phone', "%{$searchData->phone}%")
            ->orWhere('c.author IN (:users)')->setParameter('users', $searchData->users);

        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return Call[] Returns an array of Call objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

        $query = $this->createQueryBuilder('c')
            ->join('c.client', 'cl')->addSelect('cl')
            ->orWhere('cl.phone LIKE :phone')->setParameter('phone', "%{$searchData->phone}%")
            ->orWhere('c.author IN (:users)')->setParameter('users', $searchData->users);


        return $query->getQuery()->getResult();
    */

    /*
    public function findOneBySomeField($value): ?Call
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
