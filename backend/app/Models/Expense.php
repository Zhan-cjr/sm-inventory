<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Expense extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'expense_account_id',
        'payment_account_id',
        'reference_number',
        'expense_date',
        'amount',
        'description',
        'created_by',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::creating(function ($expense) {
            if (empty($expense->reference_number)) {
                $expense->reference_number = 'EXP-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
            }
        });

        static::created(function ($expense) {
            app(\App\Services\AccountingService::class)->recordExpenseJournal($expense);
        });

        static::deleting(function ($expense) {
            $journals = \App\Models\JournalEntry::where('journalable_id', $expense->id)
                ->where('journalable_type', \App\Models\Expense::class)
                ->get();

            foreach ($journals as $journal) {
                \App\Models\JournalEntryLine::where('journal_entry_id', $journal->id)->delete();
                $journal->delete();
            }
        });
    }
}
