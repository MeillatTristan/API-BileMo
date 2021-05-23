<?php


namespace App\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class PaginatorService
{


    /**
     * @param QueryBuilder|Query $query
     * @param $pageSize
     * @return Paginator
     */
    public function paginate($query, $pageSize, $page)
    {

        $paginator = new Paginator($query);

        // now get one page's items:
        $data = $paginator
            ->getQuery()
            ->setFirstResult($pageSize * ($page - 1)) // set the offset
            ->setMaxResults($pageSize) // set the limit
            ->getResult();

        if ($page <= $this->lastPage($paginator)) {

            return $data;

        }

        throw new NotFoundHttpException("Only " . $this->lastPage($paginator) ." pages available");
    }

    public function total(Paginator $paginator)
    {
        return $paginator->count();
    }

    public function lastPage(Paginator $paginator)
    {
        return ceil($paginator->count() / $paginator->getQuery()->getMaxResults());
    }


}