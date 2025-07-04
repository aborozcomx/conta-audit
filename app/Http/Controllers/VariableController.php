<?php

namespace App\Http\Controllers;

use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class VariableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('Variables/Index', [
            'variables' => Variable::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Variables/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Variable::create($request->all());

        return Redirect::route('variables.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Variable $variable)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Variable $variable)
    {
        return inertia('Variables/Edit', [
            'variable' => $variable
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Variable $variable)
    {
        $variable->update($request->all());

        return Redirect::route('variables.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Variable $variable)
    {
        $variable->delete();

        return Redirect::route('variables.index');
    }
}
