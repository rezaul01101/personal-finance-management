<?php

namespace App\Http\Resources;

use App\Models\BudgetCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BudgetCategory
 */
class BudgetCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'description' => $this->description,
            'status' => $this->status->value,
            'sort_order' => $this->sort_order,
        ];
    }
}
