<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        return view('alerts.index');
    }

    public function show(Alert $alert)
    {
        return view('alerts.show', compact('alert'));
    }
}
