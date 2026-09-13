<?php

namespace App\Http\Controllers;

use App\Actions\Products\CreateProductAction;
use App\Actions\Products\UpdateProductAction;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['organization', 'category'])
            ->withCount('batches')
            ->when(! $request->user()->isAdmin(), function ($query) use ($request) {
                $query->whereIn('organization_id', $request->user()->organizations()->pluck('organizations.id'));
            })
            ->when($request->string('q')->toString(), function ($query, string $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', [
            'organizations' => $this->memberOrganizations($request),
            'categories' => ProductCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request, CreateProductAction $action): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $organization = Organization::query()->findOrFail($request->validated('organization_id'));
        abort_unless(
            $request->user()->isAdmin() || $request->user()->belongsToOrganization($organization),
            403
        );

        $product = $action->execute($request->user(), $organization, $request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produit créé.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load(['organization', 'category', 'batches' => fn ($q) => $q->latest()]);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
            'categories' => ProductCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $this->authorize('update', $product);

        $action->execute($request->user(), $product, $request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produit mis à jour.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Organization>
     */
    private function memberOrganizations(Request $request)
    {
        if ($request->user()->isAdmin()) {
            return Organization::query()->orderBy('name')->get();
        }

        return $request->user()->organizations()->orderBy('name')->get();
    }
}
