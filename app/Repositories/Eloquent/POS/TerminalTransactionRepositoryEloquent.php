<?php

namespace App\Repositories\Eloquent\POS;

use App\Criteria\POS\ListCriteria as TerminalTransactionListCriteria;
use App\Entities\POSTerminalTransaction;
use App\Entities\POSTerminalTransactionProduct;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * Class TerminalTransactionRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class TerminalTransactionRepositoryEloquent extends BaseRepository implements TerminalTransactionRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return POSTerminalTransaction::class;
    }

    public function list($filters = [], $detailFilters = null, $paymentFilters = null, $includeChildren = true)
    {
        $this->model = $this->model->with(['payments'])->orderBy('created_at', 'DESC');

        // Apply filters for the main table
        $this->pushCriteria(new TerminalTransactionListCriteria($filters))->applyCriteria();

        // Build the relationships query
        $withRelations = [];

        // Handle details with filtering
        $withRelations['details'] = function ($query) use ($detailFilters) {
            if (!empty($detailFilters)) {
                foreach ($detailFilters as $key => $value) {
                    if (!is_null($value)) {
                        if (is_array($value)) {
                            $query->whereIn($key, $value);
                        } else {
                            $query->where($key, $value);
                        }
                    }
                }
            }

            // Always order by creation date to maintain hierarchy
            $query->orderBy('created_at', 'ASC');
        };

        // Include children (modifiers and addons) if requested
        if ($includeChildren) {
            $withRelations['details.modifiers'] = function ($query) {
                $query->orderBy('created_at', 'ASC');
            };
            $withRelations['details.addons'] = function ($query) {
                $query->orderBy('created_at', 'ASC');
            };
        }

        // Handle payments with filtering
        $withRelations['payments'] = function ($query) use ($paymentFilters) {
            if (!empty($paymentFilters)) {
                foreach ($paymentFilters as $key => $value) {
                    if (!is_null($value)) {
                        if (is_array($value)) {
                            $query->whereIn($key, $value);
                        } else {
                            $query->where($key, $value);
                        }
                    }
                }
            }
        };

        $this->model = $this->model->with($withRelations);

        // Apply whereHas filters for existence checks
        $this->model = $this->model
            ->when($detailFilters, function ($q) use ($detailFilters) {
                $q->whereHas('details', function ($query) use ($detailFilters) {
                    foreach ($detailFilters as $key => $value) {
                        if (!is_null($value)) {
                            if (is_array($value)) {
                                $query->whereIn($key, $value);
                            } else {
                                $query->where($key, $value);
                            }
                        }
                    }
                });
            })
            ->when($paymentFilters, function ($q) use ($paymentFilters) {
                $q->whereHas('payments', function ($query) use ($paymentFilters) {
                    foreach ($paymentFilters as $key => $value) {
                        if (!is_null($value)) {
                            if (is_array($value)) {
                                $query->whereIn($key, $value);
                            } else {
                                $query->where($key, $value);
                            }
                        }
                    }
                });
            });

        return $this->model->get();
    }

    public function listX($filters = [], $detailFilters = null, $paymentFilters = null)
    {
        $this->model = $this->model->with(['details', 'payments'])->orderBy('bid', 'ASC');

        // apply filters for the main table
        $this->pushCriteria(new TerminalTransactionListCriteria($filters))->applyCriteria();

        // Apply filters on 'details' and 'payments'
        $this->model = $this->model
            ->when($detailFilters, function ($q) use ($detailFilters) {
                $q->whereHas('details', function ($query) use ($detailFilters) {
                    foreach ($detailFilters as $key => $value) {
                        if (!is_null($value)) {
                            $query->where($key, $value);
                        }
                    }
                });
            })
            ->when($paymentFilters, function ($q) use ($paymentFilters) {
                $q->whereHas('payments', function ($query) use ($paymentFilters) {
                    foreach ($paymentFilters as $key => $value) {
                        if (!is_null($value)) {
                            $query->where($key, $value);
                        }
                    }
                });
            })
            ->with([
                // Create a relationship query that filters details 
                'details' => function ($query) use ($detailFilters) {
                    if (!empty($detailFilters)) {
                        foreach ($detailFilters as $key => $value) {
                            if (!is_null($value)) {
                                $query->where($key, $value);
                            }
                        }
                    }
                },
                'details.modifiers', // load modifiers through created custom relation function by table itself
                'details.addons',    // load modifiers through created custom relation function by table itself
                'payments' => function ($query) use ($paymentFilters) {
                    if (!empty($paymentFilters)) {
                        foreach ($paymentFilters as $key => $value) {
                            if (!is_null($value)) {
                                $query->where($key, $value);
                            }
                        }
                    }
                },
            ]);

        return $this->model->get();
    }

    public function details($filters = null)
    {
        $model = POSTerminalTransactionProduct::where(function ($query) use ($filters) {
            if (isset($filters)) {
                if (! empty($filters->terminal_transaction_bid)) {
                    $query->where('terminal_transaction_bid', $filters->terminal_transaction_bid);
                }

                if (! empty($filters->usage_type)) {
                    $query->where('usage_type', $filters->usage_type);
                }

                if (! empty($filters->product_bid)) {
                    $query->where('product_bid', $filters->product_bid);
                }

                if (! empty($filters->parent_bid)) {
                    $query->where('parent_bid', $filters->parent_bid);
                }

                if (! empty($filters->cart_bid)) {
                    $query->where('cart_bid', $filters->cart_bid);
                }
            }
        });

        return $model->get();
    }
}
