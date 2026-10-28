<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;

trait HasInvoiceNumber
{
    protected static $hasManualRef = [];

    public static function bootHasInvoiceNumber(): void
    {
        static::creating(function ($model) {
            if (empty($model->invoice_number)) {
                $model->invoice_number = static::generateInvoiceNumber();
            }
            // capture optional manual (paper) reference from the incoming request
            $cls = get_class($model);
            static::$hasManualRef[$cls] ??= Schema::hasColumn($model->getTable(), 'manual_reference_no');
            if (static::$hasManualRef[$cls] && empty($model->manual_reference_no) && ($ref = request('manual_reference_no'))) {
                $model->manual_reference_no = $ref;
            }
        });
    }

    protected static function generateInvoiceNumber(): string
    {
        $prefix = static::$invoicePrefix ?? strtoupper(substr((new static)->getTable(), 0, 3));
        // ponytail: max(id)+1 can collide on concurrent creates; on duplicate, retry with a random suffix
        $number = $prefix . '-' . date('Y') . '-' . str_pad((int) (static::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
        return static::where('invoice_number', $number)->exists()
            ? $number . '-' . random_int(100, 999)
            : $number;
    }
}
