<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoice_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'kode_barang',
        'nama_item',
        'qty',
        'harga',
        'diskon',
        'total',
    ];

    /**
     * Mendefinisikan relasi inverse one-to-one atau many-to-one.
     * Sebuah detail item dimiliki oleh satu invoice.
     */
    public function invoice()
    {
        return $this->belongsTo(FinanceInvoice::class, 'invoice_id', 'id');
    }
}
