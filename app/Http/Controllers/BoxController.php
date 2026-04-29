<?php

namespace App\Http\Controllers;

use App\Models\MysteryBox;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoxController extends Controller
{
    public function index(Request $request): View
    {
        $boxes = MysteryBox::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('boxes.index', ['boxes' => $boxes]);
    }

    public function show(Request $request, string $slug): View
    {
        $box = MysteryBox::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $items = $box->activeItems()->get();

        return view('boxes.show', [
            'box' => $box,
            'items' => $items,
        ]);
    }
}
