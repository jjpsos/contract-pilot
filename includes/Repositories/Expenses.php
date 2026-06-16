<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Expense;

defined('ABSPATH') || exit;

/**
 * Repository-style facade for expense records.
 *
 * Container: contract_pilot()->expenses. Use for get(), query(), delete().
 * For admin save/update workflows, use {@see \Jjpsos\ContractPilot\Services\ExpenseService}.
 */
class Expenses
{
    public function get($expense)
    {
        return Expense::find($expense);
    }


    public function insert($data, $wp_error = true)
    {
        return Expense::insert($data, $wp_error);
    }


    public function delete($id)
    {
        $expense = $this->get($id);
        if (! $expense) {
            return false;
        }

        return $expense->delete();
    }


    public function query($args = array(), $count = false)
    {
        if ($count) {
            return Expense::count($args);
        }

        return Expense::results($args);
    }
}
