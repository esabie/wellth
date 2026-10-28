<?php

namespace App\Support;

class GhanaLocations
{
    /**
     * @return array<string, list<string>>
     */
    public function regions(): array
    {
        return [
            'Greater Accra' => [
                'Accra',
                'Tema',
                'Madina',
                'East Legon',
                'Osu',
                'Cantonments',
                'Airport Residential',
                'Labone',
                'Spintex',
                'Kasoa',
                'Kasoa New Town',
                'Ablekuma',
                'Dansoman',
                'Adenta',
                'Dodowa',
            ],
            'Ashanti' => [
                'Kumasi',
                'Ejisu',
                'Obuasi',
                'Konongo',
                'Mampong',
            ],
            'Central' => [
                'Cape Coast',
                'Winneba',
                'Elmina',
                'Kasoa',
                'Mankessim',
            ],
            'Eastern' => [
                'Koforidua',
                'Nkawkaw',
                'Nsawam',
                'Akim Oda',
                'Suhum',
            ],
            'Western' => [
                'Takoradi',
                'Sekondi',
                'Tarkwa',
                'Axim',
            ],
            'Volta' => [
                'Ho',
                'Hohoe',
                'Keta',
                'Aflao',
            ],
            'Northern' => [
                'Tamale',
                'Yendi',
                'Savelugu',
            ],
            'Upper East' => [
                'Bolgatanga',
                'Bawku',
                'Navrongo',
            ],
            'Upper West' => [
                'Wa',
                'Jirapa',
                'Lawra',
            ],
            'Bono' => [
                'Sunyani',
                'Berekum',
                'Dormaa Ahenkro',
            ],
            'Bono East' => [
                'Techiman',
                'Kintampo',
                'Nkoranza',
            ],
            'Ahafo' => [
                'Goaso',
                'Bechem',
                'Duayaw Nkwanta',
            ],
            'Western North' => [
                'Sefwi Wiawso',
                'Bibiani',
                'Enchi',
            ],
            'Oti' => [
                'Dambai',
                'Jasikan',
                'Kadjebi',
            ],
            'North East' => [
                'Nalerigu',
                'Walewale',
                'Gambaga',
            ],
            'Savannah' => [
                'Damongo',
                'Bole',
                'Salaga',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function regionNames(): array
    {
        return array_keys($this->regions());
    }

    /**
     * @return list<string>
     */
    public function citiesFor(string $region): array
    {
        return $this->regions()[$region] ?? [];
    }
}
