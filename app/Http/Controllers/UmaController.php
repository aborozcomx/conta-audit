<?php

namespace App\Http\Controllers;

use App\Models\Uma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class UmaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('Umas/Index', [
            'umas' => Uma::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Umas/Create', [
            'years' => getYears(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Uma::create($request->all());

        return Redirect::route('umas.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Uma $uma)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Uma $uma)
    {
        return inertia('Umas/Edit', [
            'uma' => $uma,
            'years' => getYears(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Uma $uma)
    {
        $uma->update($request->all());

        return Redirect::route('umas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Uma $uma)
    {
        $uma->delete();

        return Redirect::route('umas.index');
    }
}
