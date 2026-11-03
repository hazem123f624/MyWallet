<?php

namespace Modules\Settings\Models;

use App\Http\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Settings\DTOs\SettingsDTO;
use Modules\Settings\Enums\SettingsType;

/**
 * @property int $id
 * @property string $type
 * @property string|null $key
 * @property string|null $name_ar
 * @property string|null $name_en
 * @property array<array-key, mixed>|null $data
 * @property int|null $setting_id
 * @property int $is_active
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Settings> $children
 * @property-read int|null $children_count
 * @property-read Settings|null $parent
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereNameAr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereNameEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereSettingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Settings withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Settings extends Model
{
    use SoftDeletes;
    use TranslateTrait;

    protected $table = 'settings';

    public $dto = SettingsDTO::class;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'data',
        'type',
        'key',
        'setting_id',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'data' => 'json',
        'created_at' => 'datetime:d-m-Y H:i:s',
    ];

    public $timestamps = true;

    public $attributes = [];

    public string $foreignKey = 'setting_id';

    public array $translatedAttributes = [];

    public array $relatedModules = [];

    public function children()
    {

        return $this->hasMany(Settings::class, 'setting_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Settings::class, 'setting_id', 'id');
    }

    public function scopeCountries($query)
    {
        return $query->where('type', SettingsType::COUNTRY->value);
    }

    public function scopeCities($query)
    {
        return $query->where('type', SettingsType::CITY->value);
    }

    public function scopeNationalities($query)
    {
        return $query->where('type', SettingsType::NATIONALITY->value);
    }

    public function searchCountries($search)
    {
        return self::countries()->where(function ($query) use ($search): void {
            $query->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%");
        })->where('is_active', true)->get();
    }

    public function searchCities($search)
    {
        return self::cities()->where(function ($query) use ($search): void {
            $query->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%");
        })->where('is_active', true)->get();
    }

    public function cities(): HasMany
    {
        return $this->hasMany(Settings::class, 'setting_id', 'id')
            ->where('type', SettingsType::CITY->value);
    }

    protected static function booted()
    {
        static::deleting(function ($setting): void {
            if ($setting->type === SettingsType::COUNTRY->value) {
                $setting->children()->delete();
            }

        });
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->name_ar ?? $this->name_en)
                : ($this->name_en ?? $this->name_ar)
        );
    }
}
