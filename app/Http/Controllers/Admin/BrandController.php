<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->status !== null, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Brands/Index', [
            'brands' => $brands,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:brands',
                'logo' => 'nullable|image|max:1024',
                'status' => 'boolean',
            ]);

            $baseSlug = Str::slug($request->name) ?: 'brand';
            $validated['slug'] = $baseSlug.'-'.Str::uuid();

            if ($request->hasFile('logo')) {
                $validated['logo'] = $request->file('logo')->store('brands', 'public');
            }

            $record = Brand::create($validated);

            if ($request->expectsJson()) {
                return response()->json(['data' => $record], 201);
            }

            return redirect()->back()->with('success', 'Brand created successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                report($e);

                return response()->json(['message' => 'Unable to save. Please try again.'], 500);
            }

            return redirect()->back()->with('error', 'Failed to create brand: '.$e->getMessage());
        }
    }

    public function update(Request $request, Brand $brand)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:brands,name,'.$brand->id,
                'logo' => 'nullable|image|max:1024',
                'status' => 'boolean',
            ]);

            $validated['slug'] = Str::slug($request->name);

            if ($request->hasFile('logo')) {
                if ($brand->logo) {
                    Storage::disk('public')->delete($brand->logo);
                }
                $validated['logo'] = $request->file('logo')->store('brands', 'public');
            }

            // Don't override existing logo if no new logo is uploaded
            if (! $request->hasFile('logo')) {
                unset($validated['logo']);
            }

            $brand->update($validated);

            return redirect()->back()->with('success', 'Brand updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update brand: '.$e->getMessage());
        }
    }

    public function destroy(Brand $brand)
    {
        try {
            if ($brand->products()->exists()) {
                return redirect()->back()->with('error', 'Cannot delete brand as it has associated products.');
            }

            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }

            $brand->delete();

            return redirect()->back()->with('success', 'Brand deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete brand: '.$e->getMessage());
        }
    }
}
