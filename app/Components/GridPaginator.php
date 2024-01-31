<?php

declare(strict_types=1);

namespace App\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends LengthAwarePaginator<Model>
 */
class GridPaginator extends LengthAwarePaginator
{
    // public function loopedRank($loop)
    // {
    //     if (!$this->sortDirection) {
    //         return null;
    //     }
    //     return $this->sortDirection === 'asc'
    //         ? ($this->total - $this->perPage * ($this->currentPage() - 1) - $loop->index)
    //         : ($this->perPage * ($this->currentPage() - 1) + $loop->iteration);
    // }
}
