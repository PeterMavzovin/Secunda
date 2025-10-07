<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Organization",
 *     title="Организация",
 *     description="Модель организации",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="ООО Ромашка"),
 *     @OA\Property(property="building_id", type="integer", example=3),
 *     @OA\Property(
 *         property="phones",
 *         type="array",
 *         @OA\Items(type="string", example="+7 (900) 123-45-67")
 *     ),
 *     @OA\Property(
 *         property="activities",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Activity")
 *     ),
 *     @OA\Property(
 *         property="building",
 *         ref="#/components/schemas/Building"
 *     )
 * )
 */
class Organization extends Model
{
    protected $fillable = ['name', 'building_id'];

    public function phones()
    {
        return $this->hasMany(Phone::class);
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'organization_activity');
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }
}
