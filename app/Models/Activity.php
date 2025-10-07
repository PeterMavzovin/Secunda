<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

/**
 * @OA\Schema(
 *     schema="Activity",
 *     type="object",
 *     title="Activity",
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="name", type="string", example="Еда"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="_lft", type="integer", example=1),
 *     @OA\Property(property="_rgt", type="integer", example=8),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

class Activity extends Model
{

    use NodeTrait;

    protected $fillable = ['name', 'parent_id'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->parent_id) {
                $parent = self::find($model->parent_id);
                if ($parent) {
                    $depth = $parent->ancestors()->count() + 1;
                    if ($depth >= 3) {
                        throw new \Exception('Нельзя создать деятельность глубже 3-го уровня');
                    }
                }
            }
        });
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_activity');
    }
}
