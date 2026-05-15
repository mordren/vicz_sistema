<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use Illuminate\Http\Request;

class AccountPayableController extends Controller
{
    public function index()
    {
        return view('payables.index');
    }

    public function create()
    {
        return view('payables.create');
    }

    public function store(Request $request)
    {
        abort(501);
    }

    public function show(AccountPayable $payable)
    {
        return view('payables.show', compact('payable'));
    }

    public function edit(AccountPayable $payable)
    {
        return view('payables.edit', compact('payable'));
    }

    public function update(Request $request, AccountPayable $payable)
    {
        abort(501);
    }

    public function destroy(AccountPayable $payable)
    {
        abort(501);
    }
}
