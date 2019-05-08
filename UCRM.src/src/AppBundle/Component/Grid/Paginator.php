<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Grid;

use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

class Paginator extends ORMPaginator
{
    /**
     * @var int
     */
    private $maxRecords;

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var int
     */
    private $stepsAroundCurrent = 2;

    /**
     * {@inheritdoc}
     */
    public function __construct($query, $fetchJoinCollection = true)
    {
        parent::__construct($query, $fetchJoinCollection);
    }

    /**
     * @return bool
     */
    public function isFirst()
    {
        return $this->page === 1;
    }

    /**
     * @return bool
     */
    public function isLast()
    {
        return $this->page === $this->getPageCount();
    }

    public function getPageCount(): int
    {
        if ($this->maxRecords > 0) {
            return (int) (floor($this->count() / $this->maxRecords) + ($this->count() % $this->maxRecords ? 1 : 0));
        }

        return 1;
    }

    public function getMaxRecords()
    {
        return $this->maxRecords;
    }

    public function setMaxRecords($maxRecords)
    {
        $this->maxRecords = $maxRecords;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page)
    {
        $this->page = $page;
    }

    public function getSteps(): array
    {
        $pageCount = $this->getPageCount();

        if ($pageCount > 1) {
            $arr = range(
                max(1, $this->page - $this->stepsAroundCurrent),
                min($pageCount, $this->page + $this->stepsAroundCurrent)
            );
            $count = 4;
            $quotient = ($pageCount - 1) / $count;
            for ($i = 0; $i <= $count; ++$i) {
                $arr[] = (int) (round($quotient * $i) + 1);
            }
            sort($arr);
            $steps = array_values(array_unique($arr));
        } else {
            $steps = [$this->page];
        }

        return $steps;
    }
}
