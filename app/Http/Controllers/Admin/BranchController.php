<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $branches = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Branches/Index', [
            'branches' => $branches,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_main' => 'nullable|boolean',
            'status' => 'required|string|in:active,inactive',
        ]);

        if (!empty($validated['is_main']) && $validated['is_main']) {
            Branch::where('is_main', true)->update(['is_main' => false]);
        }

        Branch::create($validated);

        return redirect()->back()->with('success', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $branch->id,
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_main' => 'nullable|boolean',
            'status' => 'required|string|in:active,inactive',
        ]);

        if (!empty($validated['is_main']) && $validated['is_main']) {
            Branch::where('id', '!=', $branch->id)->where('is_main', true)->update(['is_main' => false]);
        }

        $branch->update($validated);

        return redirect()->back()->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->is_main) {
            return redirect()->back()->with('error', 'Cannot delete main branch.');
        }

        $branch->delete();

        return redirect()->back()->with('success', 'Branch deleted successfully.');
    }
}
