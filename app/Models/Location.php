<?php

namespace App\Models;

use App\Attributes\Icon;
use App\Traits\HasPropertyIcons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;
use SDamian\Larasort\AutoSortable;

#[Icon('map-pin', for: 'name')]
#[Icon('home', for: 'address')]
#[Icon('world-latitude', for: 'latitude')]
#[Icon('world-longitude', for: 'longitude')]
#[Icon('map', for: 'map_url')]
#[Icon('pencil-bolt', for: 'created_at')]
#[Icon('pencil-up', for: 'updated_at')]
class Location extends Model
{
    use AutoSortable;
    use HasFactory;
    use HasPropertyIcons;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
    ];

    protected $visible = [
        'name',
        'address',
        'latitude',
        'longitude',
        'map_url',
        'created_at',
        'updated_at',
    ];

    public array $column_labels = ['map_url' => 'Map'];

    public array $sortables = [
        'name',
        'address',
        'created_at',
        'updated_at',
    ];

    public function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function term_dates(): HasMany
    {
        return $this->hasMany(TermDate::class);
    }

    /** Where the location falls in OpenStreetMap's grid of map tiles at a zoom level, in tiles, or null without coordinates. */
    public function tilePosition(int $zoom): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $tiles = 2 ** $zoom;

        return [
            ($this->longitude + 180) / 360 * $tiles,
            (1 - asinh(tan(deg2rad($this->latitude))) / M_PI) / 2 * $tiles,
        ];
    }

    /** OpenStreetMap's page for the location: a pin at its coordinates, or else a search for its address or name. */
    public function getMapUrlAttribute(): string
    {
        if ($this->latitude !== null && $this->longitude !== null) {
            return 'https://www.openstreetmap.org/?'.http_build_query(['mlat' => $this->latitude, 'mlon' => $this->longitude]).'#map=17/'.$this->latitude.'/'.$this->longitude;
        }

        return 'https://www.openstreetmap.org/search?'.http_build_query(['query' => $this->address ?: $this->name]);
    }

    /** Fills in the coordinates from the address with OpenStreetMap's Nominatim, leaving them empty if it finds nothing. */
    public function geocode(): void
    {
        if (blank($this->address) || ($this->latitude !== null && $this->longitude !== null)) {
            return;
        }

        try {
            $result = Http::withUserAgent(config('app.name').' (Readererer)')
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', ['q' => $this->address, 'format' => 'jsonv2', 'limit' => 1])
                ->json(0);
        } catch (\Throwable) {
            return;
        }

        if (isset($result['lat'], $result['lon'])) {
            $this->latitude = (float) $result['lat'];
            $this->longitude = (float) $result['lon'];
        }
    }
}
