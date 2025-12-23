<?php

namespace App\Exports;

use App\Models\DataTrxSph;
use Maatwebsite\Excel\Concerns\FromCollection;

class SphExport implements FromCollection
{
    protected $status;

    public function __construct($status)
    {
        $this->status = $status;
    }

    public function collection()
    {
        $query = DataTrxSph::query();

        if ($this->status == 'waiting') {
            $query->where('status', 1);
        } elseif ($this->status == 'approved') {
            $query->where('status', 4);
        } elseif ($this->status == 'revisi') {
            $query->where('status', 2);
        } elseif ($this->status == 'reject') {
            $query->where('status', 3);
        }

        return $query->get();
    }
}