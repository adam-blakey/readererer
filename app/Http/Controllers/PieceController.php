<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Http\Requests\StorePieceRequest;
use App\Http\Requests\UpdatePieceRequest;

class PieceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pieces = Piece::latest()->with(['composer'])->autosort()->paginate(10);

        return view('pieces.index', [
            'pieces' => $pieces,
            'page_name' => 'Pieces'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePieceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Piece $piece)
    {
        return view('pieces.show', [
            'piece' => $piece,
            'page_name' => $piece->title
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Piece $piece)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePieceRequest $request, Piece $piece)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Piece $piece)
    {
        //
    }
}