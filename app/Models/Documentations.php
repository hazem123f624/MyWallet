<?php

namespace App\Models;

use App\Http\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Model;
use App\Services\Documentations\DTOs\DocumentationDTO;

/**
 * @property int $id
 * @property string $name
 * @property int $section
 * @property string|null $category
 * @property int $sorting
 * @property string|null $short_description
 * @property string|null $description
 * @property string|null $headers
 * @property string|null $notes
 * @property string|null $query_params
 * @property string|null $body
 * @property string|null $method
 * @property string|null $route
 * @property string|null $success_response
 * @property string|null $error_response
 * @property int $developer_tested
 * @property int $tester_tested
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereDeveloperTested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereErrorResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereQueryParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereSection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereShortDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereSorting($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereSuccessResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereTesterTested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documentations whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Documentations extends Model
{
    use TranslateTrait;

    protected $table = 'documentations';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'short_description',
        'description',
        'category',
        'section',
        'route',
        'method',
        'headers',
        'notes',
        'query_params',
        'body',
        'success_response',
        'error_response',
        'developer_tested',
        'tester_tested',
        'is_active',
        'sorting',
        'created_at',
        'updated_at',
        'deleted_at',

    ];

    public $dto = DocumentationDTO::class;

    /**
     * The attributes that are mass assignable.
     */
    protected $hidden = [];

    protected $casts = [
        'id' => 'integer',
    ];

    public $timestamps = true;

    public $attributes = [];

    public array $translatedAttributes = [];

    public array $relatedModules = [];
}
