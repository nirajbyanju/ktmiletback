<?php
// database/seeders/NepalLocationSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Data\Province;
use App\Models\Data\District;
use App\Models\Data\Municipality;
use App\Models\Data\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NepalLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        
        // Clear existing data
        Ward::truncate();
        Municipality::truncate();
        District::truncate();
        Province::truncate();
        
        // Reset auto-increment counters
        DB::statement('ALTER TABLE data_provinces AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE data_districts AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE data_municipalities AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE data_wards AUTO_INCREMENT = 1');
        
        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get current user ID for created_by
        $userId = 1; // Default user ID, adjust as needed

        // ==================== PROVINCES (IDs 1-7) ====================
        $provinces = [
            ['id' => 1, 'label' => 'Province No. 1', 'slug' => 'province-no-1'],
            ['id' => 2, 'label' => 'Province No. 2', 'slug' => 'province-no-2'],
            ['id' => 3, 'label' => 'Bagmati Province', 'slug' => 'bagmati-province'],
            ['id' => 4, 'label' => 'Gandaki Province', 'slug' => 'gandaki-province'],
            ['id' => 5, 'label' => 'Lumbini Province', 'slug' => 'lumbini-province'],
            ['id' => 6, 'label' => 'Karnali Province', 'slug' => 'karnali-province'],
            ['id' => 7, 'label' => 'Sudurpashchim Province', 'slug' => 'sudurpashchim-province'],
        ];

        foreach ($provinces as $provinceData) {
            $provinceData['created_by'] = $userId;
            Province::create($provinceData);
        }

        // ==================== DISTRICTS (with proper province IDs) ====================
        $districts = [
            // Province 1 Districts (province_id = 1)
            ['id' => 1, 'label' => 'Taplejung', 'slug' => 'taplejung', 'data_province_id' => 1],
            ['id' => 2, 'label' => 'Panchthar', 'slug' => 'panchthar', 'data_province_id' => 1],
            ['id' => 3, 'label' => 'Ilam', 'slug' => 'ilam', 'data_province_id' => 1],
            ['id' => 4, 'label' => 'Jhapa', 'slug' => 'jhapa', 'data_province_id' => 1],
            ['id' => 5, 'label' => 'Morang', 'slug' => 'morang', 'data_province_id' => 1],
            ['id' => 6, 'label' => 'Sunsari', 'slug' => 'sunsari', 'data_province_id' => 1],
            ['id' => 7, 'label' => 'Dhankuta', 'slug' => 'dhankuta', 'data_province_id' => 1],
            ['id' => 8, 'label' => 'Terhathum', 'slug' => 'terhathum', 'data_province_id' => 1],
            ['id' => 9, 'label' => 'Sankhuwasabha', 'slug' => 'sankhuwasabha', 'data_province_id' => 1],
            ['id' => 10, 'label' => 'Bhojpur', 'slug' => 'bhojpur', 'data_province_id' => 1],
            ['id' => 11, 'label' => 'Solukhumbu', 'slug' => 'solukhumbu', 'data_province_id' => 1],
            ['id' => 12, 'label' => 'Khotang', 'slug' => 'khotang', 'data_province_id' => 1],
            ['id' => 13, 'label' => 'Okhaldhunga', 'slug' => 'okhaldhunga', 'data_province_id' => 1],
            ['id' => 14, 'label' => 'Udayapur', 'slug' => 'udayapur', 'data_province_id' => 1],

            // Province 2 Districts (province_id = 2)
            ['id' => 15, 'label' => 'Saptari', 'slug' => 'saptari', 'data_province_id' => 2],
            ['id' => 16, 'label' => 'Siraha', 'slug' => 'siraha', 'data_province_id' => 2],
            ['id' => 17, 'label' => 'Dhanusa', 'slug' => 'dhanusa', 'data_province_id' => 2],
            ['id' => 18, 'label' => 'Mahottari', 'slug' => 'mahottari', 'data_province_id' => 2],
            ['id' => 19, 'label' => 'Sarlahi', 'slug' => 'sarlahi', 'data_province_id' => 2],
            ['id' => 20, 'label' => 'Rautahat', 'slug' => 'rautahat', 'data_province_id' => 2],
            ['id' => 21, 'label' => 'Bara', 'slug' => 'bara', 'data_province_id' => 2],
            ['id' => 22, 'label' => 'Parsa', 'slug' => 'parsa', 'data_province_id' => 2],

            // Bagmati Province Districts (province_id = 3)
            ['id' => 23, 'label' => 'Dolakha', 'slug' => 'dolakha', 'data_province_id' => 3],
            ['id' => 24, 'label' => 'Sindhupalchok', 'slug' => 'sindhupalchok', 'data_province_id' => 3],
            ['id' => 25, 'label' => 'Rasuwa', 'slug' => 'rasuwa', 'data_province_id' => 3],
            ['id' => 26, 'label' => 'Dhading', 'slug' => 'dhading', 'data_province_id' => 3],
            ['id' => 27, 'label' => 'Nuwakot', 'slug' => 'nuwakot', 'data_province_id' => 3],
            ['id' => 28, 'label' => 'Kathmandu', 'slug' => 'kathmandu', 'data_province_id' => 3],
            ['id' => 29, 'label' => 'Bhaktapur', 'slug' => 'bhaktapur', 'data_province_id' => 3],
            ['id' => 30, 'label' => 'Lalitpur', 'slug' => 'lalitpur', 'data_province_id' => 3],
            ['id' => 31, 'label' => 'Kavrepalanchok', 'slug' => 'kavrepalanchok', 'data_province_id' => 3],
            ['id' => 32, 'label' => 'Ramechhap', 'slug' => 'ramechhap', 'data_province_id' => 3],
            ['id' => 33, 'label' => 'Sindhuli', 'slug' => 'sindhuli', 'data_province_id' => 3],
            ['id' => 34, 'label' => 'Makawanpur', 'slug' => 'makawanpur', 'data_province_id' => 3],
            ['id' => 35, 'label' => 'Chitwan', 'slug' => 'chitwan', 'data_province_id' => 3],

            // Gandaki Province Districts (province_id = 4)
            ['id' => 36, 'label' => 'Gorkha', 'slug' => 'gorkha', 'data_province_id' => 4],
            ['id' => 37, 'label' => 'Lamjung', 'slug' => 'lamjung', 'data_province_id' => 4],
            ['id' => 38, 'label' => 'Tanahun', 'slug' => 'tanahun', 'data_province_id' => 4],
            ['id' => 39, 'label' => 'Kaski', 'slug' => 'kaski', 'data_province_id' => 4],
            ['id' => 40, 'label' => 'Manang', 'slug' => 'manang', 'data_province_id' => 4],
            ['id' => 41, 'label' => 'Mustang', 'slug' => 'mustang', 'data_province_id' => 4],
            ['id' => 42, 'label' => 'Myagdi', 'slug' => 'myagdi', 'data_province_id' => 4],
            ['id' => 43, 'label' => 'Parbat', 'slug' => 'parbat', 'data_province_id' => 4],
            ['id' => 44, 'label' => 'Syangja', 'slug' => 'syangja', 'data_province_id' => 4],
            ['id' => 45, 'label' => 'Baglung', 'slug' => 'baglung', 'data_province_id' => 4],
            ['id' => 46, 'label' => 'Nawalpur', 'slug' => 'nawalpur', 'data_province_id' => 4],

            // Lumbini Province Districts (province_id = 5)
            ['id' => 47, 'label' => 'Rukum East', 'slug' => 'rukum-east', 'data_province_id' => 5],
            ['id' => 48, 'label' => 'Rolpa', 'slug' => 'rolpa', 'data_province_id' => 5],
            ['id' => 49, 'label' => 'Pyuthan', 'slug' => 'pyuthan', 'data_province_id' => 5],
            ['id' => 50, 'label' => 'Gulmi', 'slug' => 'gulmi', 'data_province_id' => 5],
            ['id' => 51, 'label' => 'Arghakhanchi', 'slug' => 'arghakhanchi', 'data_province_id' => 5],
            ['id' => 52, 'label' => 'Palpa', 'slug' => 'palpa', 'data_province_id' => 5],
            ['id' => 53, 'label' => 'Rupandehi', 'slug' => 'rupandehi', 'data_province_id' => 5],
            ['id' => 54, 'label' => 'Kapilvastu', 'slug' => 'kapilvastu', 'data_province_id' => 5],
            ['id' => 55, 'label' => 'Dang', 'slug' => 'dang', 'data_province_id' => 5],
            ['id' => 56, 'label' => 'Banke', 'slug' => 'banke', 'data_province_id' => 5],
            ['id' => 57, 'label' => 'Bardiya', 'slug' => 'bardiya', 'data_province_id' => 5],
            ['id' => 58, 'label' => 'Nawalparasi West', 'slug' => 'nawalparasi-west', 'data_province_id' => 5],

            // Karnali Province Districts (province_id = 6)
            ['id' => 59, 'label' => 'Dolpa', 'slug' => 'dolpa', 'data_province_id' => 6],
            ['id' => 60, 'label' => 'Mugu', 'slug' => 'mugu', 'data_province_id' => 6],
            ['id' => 61, 'label' => 'Humla', 'slug' => 'humla', 'data_province_id' => 6],
            ['id' => 62, 'label' => 'Jumla', 'slug' => 'jumla', 'data_province_id' => 6],
            ['id' => 63, 'label' => 'Kalikot', 'slug' => 'kalikot', 'data_province_id' => 6],
            ['id' => 64, 'label' => 'Dailekh', 'slug' => 'dailekh', 'data_province_id' => 6],
            ['id' => 65, 'label' => 'Jajarkot', 'slug' => 'jajarkot', 'data_province_id' => 6],
            ['id' => 66, 'label' => 'Rukum West', 'slug' => 'rukum-west', 'data_province_id' => 6],
            ['id' => 67, 'label' => 'Salyan', 'slug' => 'salyan', 'data_province_id' => 6],
            ['id' => 68, 'label' => 'Surkhet', 'slug' => 'surkhet', 'data_province_id' => 6],

            // Sudurpashchim Province Districts (province_id = 7)
            ['id' => 69, 'label' => 'Bajura', 'slug' => 'bajura', 'data_province_id' => 7],
            ['id' => 70, 'label' => 'Bajhang', 'slug' => 'bajhang', 'data_province_id' => 7],
            ['id' => 71, 'label' => 'Darchula', 'slug' => 'darchula', 'data_province_id' => 7],
            ['id' => 72, 'label' => 'Baitadi', 'slug' => 'baitadi', 'data_province_id' => 7],
            ['id' => 73, 'label' => 'Dadeldhura', 'slug' => 'dadeldhura', 'data_province_id' => 7],
            ['id' => 74, 'label' => 'Doti', 'slug' => 'doti', 'data_province_id' => 7],
            ['id' => 75, 'label' => 'Achham', 'slug' => 'achham', 'data_province_id' => 7],
            ['id' => 76, 'label' => 'Kailali', 'slug' => 'kailali', 'data_province_id' => 7],
            ['id' => 77, 'label' => 'Kanchanpur', 'slug' => 'kanchanpur', 'data_province_id' => 7],
        ];

        foreach ($districts as $districtData) {
            $districtData['created_by'] = $userId;
            District::create($districtData);
        }

        // ==================== MUNICIPALITIES (with proper district IDs) ====================
        $municipalities = [
            // Province 1 - Jhapa District (district_id = 4)
            ['id' => 1, 'label' => 'Bhadrapur Municipality', 'slug' => 'bhadrapur-municipality', 'data_district_id' => 4],
            ['id' => 2, 'label' => 'Birtamod Municipality', 'slug' => 'birtamod-municipality', 'data_district_id' => 4],
            ['id' => 3, 'label' => 'Mechinagar Municipality', 'slug' => 'mechinagar-municipality', 'data_district_id' => 4],
            ['id' => 4, 'label' => 'Damak Municipality', 'slug' => 'damak-municipality', 'data_district_id' => 4],
            ['id' => 5, 'label' => 'Kankai Municipality', 'slug' => 'kankai-municipality', 'data_district_id' => 4],
            ['id' => 6, 'label' => 'Arjundhara Municipality', 'slug' => 'arjundhara-municipality', 'data_district_id' => 4],
            ['id' => 7, 'label' => 'Gauradaha Municipality', 'slug' => 'gauradaha-municipality', 'data_district_id' => 4],
            ['id' => 8, 'label' => 'Shivasatakshi Municipality', 'slug' => 'shivasatakshi-municipality', 'data_district_id' => 4],
            ['id' => 9, 'label' => 'Haldibari Rural Municipality', 'slug' => 'haldibari-rural-municipality', 'data_district_id' => 4],
            
            // Province 1 - Morang District (district_id = 5)
            ['id' => 10, 'label' => 'Biratnagar Metropolitan City', 'slug' => 'biratnagar-metropolitan-city', 'data_district_id' => 5],
            ['id' => 11, 'label' => 'Sundarharaicha Municipality', 'slug' => 'sundarharaicha-municipality', 'data_district_id' => 5],
            ['id' => 12, 'label' => 'Belbari Municipality', 'slug' => 'belbari-municipality', 'data_district_id' => 5],
            ['id' => 13, 'label' => 'Pathari Shanischare Municipality', 'slug' => 'pathari-shanischare-municipality', 'data_district_id' => 5],
            ['id' => 14, 'label' => 'Ratuwamai Municipality', 'slug' => 'ratuwamai-municipality', 'data_district_id' => 5],
            ['id' => 15, 'label' => 'Urlabari Municipality', 'slug' => 'urlabari-municipality', 'data_district_id' => 5],
            ['id' => 16, 'label' => 'Rangeli Municipality', 'slug' => 'rangeli-municipality', 'data_district_id' => 5],
            
            // Province 1 - Sunsari District (district_id = 6)
            ['id' => 17, 'label' => 'Dharan Sub-Metropolitan City', 'slug' => 'dharan-sub-metropolitan-city', 'data_district_id' => 6],
            ['id' => 18, 'label' => 'Itahari Sub-Metropolitan City', 'slug' => 'itahari-sub-metropolitan-city', 'data_district_id' => 6],
            ['id' => 19, 'label' => 'Inaruwa Municipality', 'slug' => 'inaruwa-municipality', 'data_district_id' => 6],
            ['id' => 20, 'label' => 'Duhabi Municipality', 'slug' => 'duhabi-municipality', 'data_district_id' => 6],
            ['id' => 21, 'label' => 'Ramdhuni Municipality', 'slug' => 'ramdhuni-municipality', 'data_district_id' => 6],
            
            // Bagmati Province - Kathmandu District (district_id = 28)
            ['id' => 22, 'label' => 'Kathmandu Metropolitan City', 'slug' => 'kathmandu-metropolitan-city', 'data_district_id' => 28],
            ['id' => 23, 'label' => 'Kageshwori Manohara Municipality', 'slug' => 'kageshwori-manohara-municipality', 'data_district_id' => 28],
            ['id' => 24, 'label' => 'Gokarneshwor Municipality', 'slug' => 'gokarneshwor-municipality', 'data_district_id' => 28],
            ['id' => 25, 'label' => 'Chandragiri Municipality', 'slug' => 'chandragiri-municipality', 'data_district_id' => 28],
            ['id' => 26, 'label' => 'Tokha Municipality', 'slug' => 'tokha-municipality', 'data_district_id' => 28],
            ['id' => 27, 'label' => 'Tarakeshwor Municipality', 'slug' => 'tarakeshwor-municipality', 'data_district_id' => 28],
            ['id' => 28, 'label' => 'Nagarjun Municipality', 'slug' => 'nagarjun-municipality', 'data_district_id' => 28],
            ['id' => 29, 'label' => 'Budhanilkantha Municipality', 'slug' => 'budhanilkantha-municipality', 'data_district_id' => 28],
            ['id' => 30, 'label' => 'Shankharapur Municipality', 'slug' => 'shankharapur-municipality', 'data_district_id' => 28],
            ['id' => 31, 'label' => 'Dakshinkali Municipality', 'slug' => 'dakshinkali-municipality', 'data_district_id' => 28],
            
            // Bagmati Province - Lalitpur District (district_id = 30)
            ['id' => 32, 'label' => 'Lalitpur Metropolitan City', 'slug' => 'lalitpur-metropolitan-city', 'data_district_id' => 30],
            ['id' => 33, 'label' => 'Godawari Municipality', 'slug' => 'godawari-municipality', 'data_district_id' => 30],
            ['id' => 34, 'label' => 'Mahalaxmi Municipality', 'slug' => 'mahalaxmi-municipality', 'data_district_id' => 30],
            ['id' => 35, 'label' => 'Bagmati Rural Municipality', 'slug' => 'bagmati-rural-municipality', 'data_district_id' => 30],
            ['id' => 36, 'label' => 'Konjyosom Rural Municipality', 'slug' => 'konjyosom-rural-municipality', 'data_district_id' => 30],
            
            // Bagmati Province - Bhaktapur District (district_id = 29)
            ['id' => 37, 'label' => 'Bhaktapur Municipality', 'slug' => 'bhaktapur-municipality', 'data_district_id' => 29],
            ['id' => 38, 'label' => 'Madhyapur Thimi Municipality', 'slug' => 'madhyapur-thimi-municipality', 'data_district_id' => 29],
            ['id' => 39, 'label' => 'Changunarayan Municipality', 'slug' => 'changunarayan-municipality', 'data_district_id' => 29],
            ['id' => 40, 'label' => 'Suryabinayak Municipality', 'slug' => 'suryabinayak-municipality', 'data_district_id' => 29],
            
            // Gandaki Province - Kaski District (district_id = 39)
            ['id' => 41, 'label' => 'Pokhara Metropolitan City', 'slug' => 'pokhara-metropolitan-city', 'data_district_id' => 39],
            ['id' => 42, 'label' => 'Annapurna Rural Municipality', 'slug' => 'annapurna-rural-municipality', 'data_district_id' => 39],
            ['id' => 43, 'label' => 'Machhapuchchhre Rural Municipality', 'slug' => 'machhapuchchhre-rural-municipality', 'data_district_id' => 39],
            ['id' => 44, 'label' => 'Madi Rural Municipality', 'slug' => 'madi-rural-municipality', 'data_district_id' => 39],
            ['id' => 45, 'label' => 'Rupa Rural Municipality', 'slug' => 'rupa-rural-municipality', 'data_district_id' => 39],
            
            // Lumbini Province - Rupandehi District (district_id = 53)
            ['id' => 46, 'label' => 'Butwal Sub-Metropolitan City', 'slug' => 'butwal-sub-metropolitan-city', 'data_district_id' => 53],
            ['id' => 47, 'label' => 'Siddharthanagar Municipality', 'slug' => 'siddharthanagar-municipality', 'data_district_id' => 53],
            ['id' => 48, 'label' => 'Lumbini Sanskritik Municipality', 'slug' => 'lumbini-sanskritik-municipality', 'data_district_id' => 53],
            ['id' => 49, 'label' => 'Devdaha Municipality', 'slug' => 'devdaha-municipality', 'data_district_id' => 53],
            ['id' => 50, 'label' => 'Sainamaina Municipality', 'slug' => 'sainamaina-municipality', 'data_district_id' => 53],
            ['id' => 51, 'label' => 'Tillottama Municipality', 'slug' => 'tillottama-municipality', 'data_district_id' => 53],
            
            // Sudurpashchim Province - Kailali District (district_id = 76)
            ['id' => 52, 'label' => 'Dhangadhi Sub-Metropolitan City', 'slug' => 'dhangadhi-sub-metropolitan-city', 'data_district_id' => 76],
            ['id' => 53, 'label' => 'Tikapur Municipality', 'slug' => 'tikapur-municipality', 'data_district_id' => 76],
            ['id' => 54, 'label' => 'Ghodaghodi Municipality', 'slug' => 'ghodaghodi-municipality', 'data_district_id' => 76],
            ['id' => 55, 'label' => 'Lamkichuha Municipality', 'slug' => 'lamkichuha-municipality', 'data_district_id' => 76],
            ['id' => 56, 'label' => 'Bhajani Municipality', 'slug' => 'bhajani-municipality', 'data_district_id' => 76],
            ['id' => 57, 'label' => 'Godawari Municipality', 'slug' => 'godawari-municipality', 'data_district_id' => 76],
            ['id' => 58, 'label' => 'Gauriganga Municipality', 'slug' => 'gauriganga-municipality', 'data_district_id' => 76],
        ];

        foreach ($municipalities as $municipalityData) {
            $municipalityData['created_by'] = $userId;
            Municipality::create($municipalityData);
        }

        // ==================== WARDS (with proper municipality IDs) ====================
        // Get all municipalities and create wards
        $municipalities = Municipality::all();
        $wardId = 1;
        
        foreach ($municipalities as $municipality) {
            $wardCount = $this->getWardCountForMunicipality($municipality->label);
            
            for ($wardNumber = 1; $wardNumber <= $wardCount; $wardNumber++) {
                Ward::create([
                    'id' => $wardId++,
                    'label' => "Ward No. {$wardNumber}",
                    'slug' => Str::slug("ward-no-{$wardNumber}-{$municipality->label}"),
                    'data_municipality_id' => $municipality->id,
                    'created_by' => $userId,
                ]);
            }
        }
    }

    /**
     * Get ward count for specific municipalities
     */
    private function getWardCountForMunicipality($municipalityName): int
    {
        // Metropolitan cities typically have more wards
        if (str_contains($municipalityName, 'Metropolitan City')) {
            return 32;
        }
        
        // Sub-metropolitan cities
        if (str_contains($municipalityName, 'Sub-Metropolitan')) {
            return 24;
        }
        
        // Regular municipalities
        if (str_contains($municipalityName, 'Municipality') && !str_contains($municipalityName, 'Rural')) {
            return 14;
        }
        
        // Rural municipalities
        if (str_contains($municipalityName, 'Rural Municipality')) {
            return 7;
        }
        
        // Default
        return 9;
    }
}