<?php

namespace App\Http\Controllers;

use App\Models\MaterialPackage;
use App\Models\StockRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MaterialPackageController extends Controller
{
    public function index(Request $request)
    {
        $query = MaterialPackage::query();

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('sku_code', 'like', "%{$request->keyword}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('low_stock') && $request->low_stock == 1) {
            $query->whereColumn('stock_quantity', '<=', 'warning_quantity');
        }

        $packages = $query->orderBy('id', 'desc')->paginate($request->page_size ?? 15);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $packages,
        ]);
    }

    public function show($id)
    {
        $package = MaterialPackage::with(['stockRecords' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(50);
        }])->findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $package,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'sku_code' => 'required|string|max:50|unique:material_packages,sku_code',
            'description' => 'nullable|string',
            'items' => 'nullable|array',
            'stock_quantity' => 'required|integer|min:0',
            'warning_quantity' => 'nullable|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        $package = MaterialPackage::create($validated);

        return response()->json([
            'code' => 0,
            'message' => '材料包创建成功',
            'data' => $package,
        ]);
    }

    public function update(Request $request, $id)
    {
        $package = MaterialPackage::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:200',
            'sku_code' => 'sometimes|required|string|max:50|unique:material_packages,sku_code,' . $id,
            'description' => 'nullable|string',
            'items' => 'nullable|array',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'warning_quantity' => 'nullable|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        $package->update($validated);

        return response()->json([
            'code' => 0,
            'message' => '材料包更新成功',
            'data' => $package,
        ]);
    }

    public function destroy($id)
    {
        $package = MaterialPackage::findOrFail($id);

        $hasActiveActivities = $package->activities()
            ->whereIn('status', [0, 1])
            ->exists();

        if ($hasActiveActivities) {
            throw ValidationException::withMessages([
                'id' => ['该材料包已关联活动，无法删除'],
            ]);
        }

        $package->delete();

        return response()->json([
            'code' => 0,
            'message' => '材料包删除成功',
        ]);
    }

    public function stockIn(Request $request, $id)
    {
        $package = MaterialPackage::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'remark' => 'nullable|string',
            'operator' => 'nullable|string|max:50',
        ]);

        $package->updateStock(
            $validated['quantity'],
            StockRecord::TYPE_STOCK_IN,
            $validated['operator'] ?? 'system',
            $validated['remark'] ?? '入库操作'
        );

        return response()->json([
            'code' => 0,
            'message' => '入库成功',
            'data' => $package->fresh(),
        ]);
    }

    public function stockOut(Request $request, $id)
    {
        $package = MaterialPackage::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'remark' => 'nullable|string',
            'operator' => 'nullable|string|max:50',
        ]);

        if ($package->stock_quantity < $validated['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => ['库存不足，当前库存：' . $package->stock_quantity],
            ]);
        }

        $package->updateStock(
            -$validated['quantity'],
            StockRecord::TYPE_STOCK_OUT,
            $validated['operator'] ?? 'system',
            $validated['remark'] ?? '出库操作'
        );

        return response()->json([
            'code' => 0,
            'message' => '出库成功',
            'data' => $package->fresh(),
        ]);
    }

    public function all()
    {
        $packages = MaterialPackage::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'sku_code', 'stock_quantity', 'warning_quantity']);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $packages,
        ]);
    }
}
