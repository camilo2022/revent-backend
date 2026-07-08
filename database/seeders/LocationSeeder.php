<?php

namespace Database\Seeders;

use App\Models\Continent;
use App\Models\Region;
use App\Models\Country;
use App\Models\Department;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $continentMap  = $this->seed_continents();
        $regionMap     = $this->seed_regions($continentMap);
        $countryMap    = $this->seed_countries($regionMap);
        $departmentMap = $this->seed_departments($countryMap);
        $this->seed_cities($departmentMap);
    }

    private function seed_continents(): array
    {
        $map  = [];
        $data = json_decode(File::get(public_path('json/regions.json')), true);

        foreach (collect($data)->sortBy('id') as $row) {
            $translations = $row['translations'] ?? [];

            $continent = Continent::create([
                'name'         => $translations['es'] ?? $row['name'],
                'translations' => $translations,
                'settings'     => [
                    'wiki_data_id' => $row['wikiDataId'] ?? null,
                ],
            ]);

            $map[$row['id']] = $continent->id;
        }

        return $map;
    }

    private function seed_regions(array $continentMap): array
    {
        $map  = [];
        $data = json_decode(File::get(public_path('json/subregions.json')), true);

        foreach (collect($data)->sortBy('id') as $row) {
            $translations = $row['translations'] ?? [];

            $region = Region::create([
                'continent_id' => $continentMap[$row['region_id']] ?? null,
                'name'         => $translations['es'] ?? $row['name'],
                'translations' => $translations,
                'settings'     => [
                    'wiki_data_id' => $row['wikiDataId'] ?? null,
                ],
            ]);

            $map[$row['id']] = $region->id;
        }

        return $map;
    }

    private function seed_countries(array $regionMap): array
    {
        $map  = [];
        $data = json_decode(File::get(public_path('json/countries.json')), true);

        foreach (collect($data)->sortBy('id') as $row) {
            $country = Country::create([
                'region_id'       => $regionMap[$row['subregion_id']] ?? null,
                'name'            => $row['name'],
                'iso3'            => $row['iso3'],
                'iso2'            => $row['iso2'],
                'numeric_code'    => $row['numeric_code'],
                'phone_code'      => $row['phonecode'],
                'currency'        => $row['currency'],
                'currency_name'   => $row['currency_name'],
                'currency_symbol' => $row['currency_symbol'],
                'tld'             => $row['tld'],
                'native'          => $row['native'] ?? null,
                'nationality'     => $row['nationality'],
                'latitude'        => $row['latitude'] ?? null,
                'longitude'       => $row['longitude'] ?? null,
                'emoji'           => $row['emoji'],
                'emojiU'          => $row['emojiU'],
                'translations'    => $row['translations'] ?? [],
                'settings'        => [
                    'capital'   => $row['capital'] ?? null,
                    'timezones' => $row['timezones'] ?? [],
                ],
            ]);

            $map[$row['id']] = $country->id;
        }

        return $map;
    }

    private function seed_departments(array $countryMap): array
    {
        $map  = [];
        $data = json_decode(File::get(public_path('json/departaments.json')), true);

        foreach (collect($data)->sortBy('id') as $row) {
            $department = Department::create([
                'country_id' => $countryMap[$row['country_id']] ?? null,
                'name'       => $row['name'],
                'state_code' => $row['state_code'] ?? null,
                'type'       => $row['type'],
                'latitude'   => $row['latitude'] ?? null,
                'longitude'  => $row['longitude'] ?? null,
                'settings'   => [],
            ]);

            $map[$row['id']] = $department->id;
        }

        return $map;
    }

    private function seed_cities(array $departmentMap): void
    {
        $data = json_decode(File::get(public_path('json/cities.json')), true);

        foreach (collect($data)->sortBy('id') as $row) {
            City::create([
                'department_id' => $departmentMap[$row['state_id']] ?? null,
                'name'          => $row['name'],
                'latitude'      => $row['latitude'] ?? null,
                'longitude'     => $row['longitude'] ?? null,
                'settings'      => [
                    'wiki_data_id' => $row['wikiDataId'] ?? null,
                ],
            ]);
        }
    }
}
