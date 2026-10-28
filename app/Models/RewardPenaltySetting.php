<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardPenaltySetting extends Model
{
    protected $table = 'reward_penalty_settings';

    protected $fillable = [
        'school_id',
        'rewards_enabled',
        'penalties_enabled',
        'auto_apply',
        'reward_threshold',
        'penalty_threshold',
        'improved_bonus_points',
        'declined_penalty_points',
        'reward_catalog',
        'penalty_catalog',
    ];

    protected $casts = [
        'rewards_enabled' => 'boolean',
        'penalties_enabled' => 'boolean',
        'auto_apply' => 'boolean',
        'reward_threshold' => 'float',
        'penalty_threshold' => 'float',
        'improved_bonus_points' => 'float',
        'declined_penalty_points' => 'float',
        'reward_catalog' => 'array',
        'penalty_catalog' => 'array',
    ];

    public static function forSchool(?int $schoolId): self
    {
        $setting = static::firstOrCreate(
            ['school_id' => $schoolId],
            [
                'reward_catalog' => [
                    ['title' => 'شهادة تقدير', 'points' => 20],
                    ['title' => 'هدية عينية', 'points' => 30],
                    ['title' => 'درجة تميز', 'points' => 50],
                ],
                'penalty_catalog' => [
                    ['title' => 'تنبيه شفهي', 'points' => 5, 'severity' => 1],
                    ['title' => 'إشعار ولي الأمر', 'points' => 10, 'severity' => 2],
                    ['title' => 'حسم درجة سلوك', 'points' => 20, 'severity' => 3],
                ],
            ]
        );

        // A freshly created row only carries the given attributes; reload so the
        // database defaults for the boolean/threshold columns are populated.
        return $setting->fresh() ?? $setting;
    }
}
