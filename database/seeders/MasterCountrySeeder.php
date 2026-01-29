<?php

namespace Database\Seeders;

use App\Models\MasterCountry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Populates master_countries table with country data and phone codes
     */
    public function run(): void
    {
        // Truncate existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MasterCountry::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Phone codes mapping for common countries
        $phoneCodes = [
            'af' => '+93',   // Afghanistan
            'al' => '+355',  // Albania
            'dz' => '+213',  // Algeria
            'ad' => '+376',  // Andorra
            'ao' => '+244',  // Angola
            'ag' => '+1268', // Antigua and Barbuda
            'ar' => '+54',   // Argentina
            'am' => '+374',  // Armenia
            'au' => '+61',   // Australia
            'at' => '+43',   // Austria
            'az' => '+994',  // Azerbaijan
            'bs' => '+1242', // Bahamas
            'bh' => '+973',  // Bahrain
            'bd' => '+880',  // Bangladesh
            'bb' => '+1246', // Barbados
            'by' => '+375',  // Belarus
            'be' => '+32',   // Belgium
            'bz' => '+501',  // Belize
            'bj' => '+229',  // Benin
            'bt' => '+975',  // Bhutan
            'bo' => '+591',  // Bolivia
            'ba' => '+387',  // Bosnia and Herzegovina
            'bw' => '+267',  // Botswana
            'br' => '+55',   // Brazil
            'bn' => '+673',  // Brunei
            'bg' => '+359',  // Bulgaria
            'bf' => '+226',  // Burkina Faso
            'bi' => '+257',  // Burundi
            'cv' => '+238',  // Cabo Verde
            'kh' => '+855',  // Cambodia
            'cm' => '+237',  // Cameroon
            'ca' => '+1',    // Canada
            'cf' => '+236',  // Central African Republic
            'td' => '+235',  // Chad
            'cl' => '+56',   // Chile
            'cn' => '+86',   // China
            'co' => '+57',   // Colombia
            'km' => '+269',  // Comoros
            'cg' => '+242',  // Congo
            'cd' => '+243',  // Congo (DRC)
            'cr' => '+506',  // Costa Rica
            'ci' => '+225',  // Côte d'Ivoire
            'hr' => '+385',  // Croatia
            'cu' => '+53',   // Cuba
            'cy' => '+357',  // Cyprus
            'cz' => '+420',  // Czechia
            'dk' => '+45',   // Denmark
            'dj' => '+253',  // Djibouti
            'dm' => '+1767', // Dominica
            'do' => '+1809', // Dominican Republic
            'ec' => '+593',  // Ecuador
            'eg' => '+20',   // Egypt
            'sv' => '+503',  // El Salvador
            'gq' => '+240',  // Equatorial Guinea
            'er' => '+291',  // Eritrea
            'ee' => '+372',  // Estonia
            'sz' => '+268',  // Eswatini
            'et' => '+251',  // Ethiopia
            'fj' => '+679',  // Fiji
            'fi' => '+358',  // Finland
            'fr' => '+33',   // France
            'ga' => '+241',  // Gabon
            'gm' => '+220',  // Gambia
            'ge' => '+995',  // Georgia
            'de' => '+49',   // Germany
            'gh' => '+233',  // Ghana
            'gr' => '+30',   // Greece
            'gd' => '+1473', // Grenada
            'gt' => '+502',  // Guatemala
            'gn' => '+224',  // Guinea
            'gw' => '+245',  // Guinea-Bissau
            'gy' => '+592',  // Guyana
            'ht' => '+509',  // Haiti
            'hn' => '+504',  // Honduras
            'hu' => '+36',   // Hungary
            'is' => '+354',  // Iceland
            'in' => '+91',   // India
            'id' => '+62',   // Indonesia
            'ir' => '+98',   // Iran
            'iq' => '+964',  // Iraq
            'ie' => '+353',  // Ireland
            'il' => '+972',  // Israel
            'it' => '+39',   // Italy
            'jm' => '+1876', // Jamaica
            'jp' => '+81',   // Japan
            'jo' => '+962',  // Jordan
            'kz' => '+7',    // Kazakhstan
            'ke' => '+254',  // Kenya
            'ki' => '+686',  // Kiribati
            'kp' => '+850',  // North Korea
            'kr' => '+82',   // South Korea
            'kw' => '+965',  // Kuwait
            'kg' => '+996',  // Kyrgyzstan
            'la' => '+856',  // Laos
            'lv' => '+371',  // Latvia
            'lb' => '+961',  // Lebanon
            'ls' => '+266',  // Lesotho
            'lr' => '+231',  // Liberia
            'ly' => '+218',  // Libya
            'li' => '+423',  // Liechtenstein
            'lt' => '+370',  // Lithuania
            'lu' => '+352',  // Luxembourg
            'mg' => '+261',  // Madagascar
            'mw' => '+265',  // Malawi
            'my' => '+60',   // Malaysia
            'mv' => '+960',  // Maldives
            'ml' => '+223',  // Mali
            'mt' => '+356',  // Malta
            'mh' => '+692',  // Marshall Islands
            'mr' => '+222',  // Mauritania
            'mu' => '+230',  // Mauritius
            'mx' => '+52',   // Mexico
            'fm' => '+691',  // Micronesia
            'md' => '+373',  // Moldova
            'mc' => '+377',  // Monaco
            'mn' => '+976',  // Mongolia
            'me' => '+382',  // Montenegro
            'ma' => '+212',  // Morocco
            'mz' => '+258',  // Mozambique
            'mm' => '+95',   // Myanmar
            'na' => '+264',  // Namibia
            'nr' => '+674',  // Nauru
            'np' => '+977',  // Nepal
            'nl' => '+31',   // Netherlands
            'nz' => '+64',   // New Zealand
            'ni' => '+505',  // Nicaragua
            'ne' => '+227',  // Niger
            'ng' => '+234',  // Nigeria
            'mk' => '+389',  // North Macedonia
            'no' => '+47',   // Norway
            'om' => '+968',  // Oman
            'pk' => '+92',   // Pakistan
            'pw' => '+680',  // Palau
            'pa' => '+507',  // Panama
            'pg' => '+675',  // Papua New Guinea
            'py' => '+595',  // Paraguay
            'pe' => '+51',   // Peru
            'ph' => '+63',   // Philippines
            'pl' => '+48',   // Poland
            'pt' => '+351',  // Portugal
            'qa' => '+974',  // Qatar
            'ro' => '+40',   // Romania
            'ru' => '+7',    // Russia
            'rw' => '+250',  // Rwanda
            'kn' => '+1869', // Saint Kitts and Nevis
            'lc' => '+1758', // Saint Lucia
            'vc' => '+1784', // Saint Vincent
            'ws' => '+685',  // Samoa
            'sm' => '+378',  // San Marino
            'st' => '+239',  // Sao Tome and Principe
            'sa' => '+966',  // Saudi Arabia
            'sn' => '+221',  // Senegal
            'rs' => '+381',  // Serbia
            'sc' => '+248',  // Seychelles
            'sl' => '+232',  // Sierra Leone
            'sg' => '+65',   // Singapore
            'sk' => '+421',  // Slovakia
            'si' => '+386',  // Slovenia
            'sb' => '+677',  // Solomon Islands
            'so' => '+252',  // Somalia
            'za' => '+27',   // South Africa
            'ss' => '+211',  // South Sudan
            'es' => '+34',   // Spain
            'lk' => '+94',   // Sri Lanka
            'sd' => '+249',  // Sudan
            'sr' => '+597',  // Suriname
            'se' => '+46',   // Sweden
            'ch' => '+41',   // Switzerland
            'sy' => '+963',  // Syria
            'tj' => '+992',  // Tajikistan
            'tz' => '+255',  // Tanzania
            'th' => '+66',   // Thailand
            'tl' => '+670',  // Timor-Leste
            'tg' => '+228',  // Togo
            'to' => '+676',  // Tonga
            'tt' => '+1868', // Trinidad and Tobago
            'tn' => '+216',  // Tunisia
            'tr' => '+90',   // Türkiye
            'tm' => '+993',  // Turkmenistan
            'tv' => '+688',  // Tuvalu
            'ug' => '+256',  // Uganda
            'ua' => '+380',  // Ukraine
            'ae' => '+971',  // UAE
            'gb' => '+44',   // UK
            'us' => '+1',    // USA
            'uy' => '+598',  // Uruguay
            'uz' => '+998',  // Uzbekistan
            'vu' => '+678',  // Vanuatu
            've' => '+58',   // Venezuela
            'vn' => '+84',   // Vietnam
            'ye' => '+967',  // Yemen
            'zm' => '+260',  // Zambia
            'zw' => '+263',  // Zimbabwe
        ];

        // Countries data from countries.sql
        $countries = [
            ['id' => 4, 'alpha_2' => 'af', 'alpha_3' => 'afg', 'name' => 'Afghanistan'],
            ['id' => 8, 'alpha_2' => 'al', 'alpha_3' => 'alb', 'name' => 'Albania'],
            ['id' => 12, 'alpha_2' => 'dz', 'alpha_3' => 'dza', 'name' => 'Algeria'],
            ['id' => 20, 'alpha_2' => 'ad', 'alpha_3' => 'and', 'name' => 'Andorra'],
            ['id' => 24, 'alpha_2' => 'ao', 'alpha_3' => 'ago', 'name' => 'Angola'],
            ['id' => 28, 'alpha_2' => 'ag', 'alpha_3' => 'atg', 'name' => 'Antigua and Barbuda'],
            ['id' => 32, 'alpha_2' => 'ar', 'alpha_3' => 'arg', 'name' => 'Argentina'],
            ['id' => 51, 'alpha_2' => 'am', 'alpha_3' => 'arm', 'name' => 'Armenia'],
            ['id' => 36, 'alpha_2' => 'au', 'alpha_3' => 'aus', 'name' => 'Australia'],
            ['id' => 40, 'alpha_2' => 'at', 'alpha_3' => 'aut', 'name' => 'Austria'],
            ['id' => 31, 'alpha_2' => 'az', 'alpha_3' => 'aze', 'name' => 'Azerbaijan'],
            ['id' => 44, 'alpha_2' => 'bs', 'alpha_3' => 'bhs', 'name' => 'Bahamas'],
            ['id' => 48, 'alpha_2' => 'bh', 'alpha_3' => 'bhr', 'name' => 'Bahrain'],
            ['id' => 50, 'alpha_2' => 'bd', 'alpha_3' => 'bgd', 'name' => 'Bangladesh'],
            ['id' => 52, 'alpha_2' => 'bb', 'alpha_3' => 'brb', 'name' => 'Barbados'],
            ['id' => 112, 'alpha_2' => 'by', 'alpha_3' => 'blr', 'name' => 'Belarus'],
            ['id' => 56, 'alpha_2' => 'be', 'alpha_3' => 'bel', 'name' => 'Belgium'],
            ['id' => 84, 'alpha_2' => 'bz', 'alpha_3' => 'blz', 'name' => 'Belize'],
            ['id' => 204, 'alpha_2' => 'bj', 'alpha_3' => 'ben', 'name' => 'Benin'],
            ['id' => 64, 'alpha_2' => 'bt', 'alpha_3' => 'btn', 'name' => 'Bhutan'],
            ['id' => 68, 'alpha_2' => 'bo', 'alpha_3' => 'bol', 'name' => 'Bolivia'],
            ['id' => 70, 'alpha_2' => 'ba', 'alpha_3' => 'bih', 'name' => 'Bosnia and Herzegovina'],
            ['id' => 72, 'alpha_2' => 'bw', 'alpha_3' => 'bwa', 'name' => 'Botswana'],
            ['id' => 76, 'alpha_2' => 'br', 'alpha_3' => 'bra', 'name' => 'Brazil'],
            ['id' => 96, 'alpha_2' => 'bn', 'alpha_3' => 'brn', 'name' => 'Brunei Darussalam'],
            ['id' => 100, 'alpha_2' => 'bg', 'alpha_3' => 'bgr', 'name' => 'Bulgaria'],
            ['id' => 854, 'alpha_2' => 'bf', 'alpha_3' => 'bfa', 'name' => 'Burkina Faso'],
            ['id' => 108, 'alpha_2' => 'bi', 'alpha_3' => 'bdi', 'name' => 'Burundi'],
            ['id' => 132, 'alpha_2' => 'cv', 'alpha_3' => 'cpv', 'name' => 'Cabo Verde'],
            ['id' => 116, 'alpha_2' => 'kh', 'alpha_3' => 'khm', 'name' => 'Cambodia'],
            ['id' => 120, 'alpha_2' => 'cm', 'alpha_3' => 'cmr', 'name' => 'Cameroon'],
            ['id' => 124, 'alpha_2' => 'ca', 'alpha_3' => 'can', 'name' => 'Canada'],
            ['id' => 140, 'alpha_2' => 'cf', 'alpha_3' => 'caf', 'name' => 'Central African Republic'],
            ['id' => 148, 'alpha_2' => 'td', 'alpha_3' => 'tcd', 'name' => 'Chad'],
            ['id' => 152, 'alpha_2' => 'cl', 'alpha_3' => 'chl', 'name' => 'Chile'],
            ['id' => 156, 'alpha_2' => 'cn', 'alpha_3' => 'chn', 'name' => 'China'],
            ['id' => 170, 'alpha_2' => 'co', 'alpha_3' => 'col', 'name' => 'Colombia'],
            ['id' => 174, 'alpha_2' => 'km', 'alpha_3' => 'com', 'name' => 'Comoros'],
            ['id' => 178, 'alpha_2' => 'cg', 'alpha_3' => 'cog', 'name' => 'Congo'],
            ['id' => 180, 'alpha_2' => 'cd', 'alpha_3' => 'cod', 'name' => 'Congo (DRC)'],
            ['id' => 188, 'alpha_2' => 'cr', 'alpha_3' => 'cri', 'name' => 'Costa Rica'],
            ['id' => 384, 'alpha_2' => 'ci', 'alpha_3' => 'civ', 'name' => "Côte d'Ivoire"],
            ['id' => 191, 'alpha_2' => 'hr', 'alpha_3' => 'hrv', 'name' => 'Croatia'],
            ['id' => 192, 'alpha_2' => 'cu', 'alpha_3' => 'cub', 'name' => 'Cuba'],
            ['id' => 196, 'alpha_2' => 'cy', 'alpha_3' => 'cyp', 'name' => 'Cyprus'],
            ['id' => 203, 'alpha_2' => 'cz', 'alpha_3' => 'cze', 'name' => 'Czechia'],
            ['id' => 208, 'alpha_2' => 'dk', 'alpha_3' => 'dnk', 'name' => 'Denmark'],
            ['id' => 262, 'alpha_2' => 'dj', 'alpha_3' => 'dji', 'name' => 'Djibouti'],
            ['id' => 212, 'alpha_2' => 'dm', 'alpha_3' => 'dma', 'name' => 'Dominica'],
            ['id' => 214, 'alpha_2' => 'do', 'alpha_3' => 'dom', 'name' => 'Dominican Republic'],
            ['id' => 218, 'alpha_2' => 'ec', 'alpha_3' => 'ecu', 'name' => 'Ecuador'],
            ['id' => 818, 'alpha_2' => 'eg', 'alpha_3' => 'egy', 'name' => 'Egypt'],
            ['id' => 222, 'alpha_2' => 'sv', 'alpha_3' => 'slv', 'name' => 'El Salvador'],
            ['id' => 226, 'alpha_2' => 'gq', 'alpha_3' => 'gnq', 'name' => 'Equatorial Guinea'],
            ['id' => 232, 'alpha_2' => 'er', 'alpha_3' => 'eri', 'name' => 'Eritrea'],
            ['id' => 233, 'alpha_2' => 'ee', 'alpha_3' => 'est', 'name' => 'Estonia'],
            ['id' => 748, 'alpha_2' => 'sz', 'alpha_3' => 'swz', 'name' => 'Eswatini'],
            ['id' => 231, 'alpha_2' => 'et', 'alpha_3' => 'eth', 'name' => 'Ethiopia'],
            ['id' => 242, 'alpha_2' => 'fj', 'alpha_3' => 'fji', 'name' => 'Fiji'],
            ['id' => 246, 'alpha_2' => 'fi', 'alpha_3' => 'fin', 'name' => 'Finland'],
            ['id' => 250, 'alpha_2' => 'fr', 'alpha_3' => 'fra', 'name' => 'France'],
            ['id' => 266, 'alpha_2' => 'ga', 'alpha_3' => 'gab', 'name' => 'Gabon'],
            ['id' => 270, 'alpha_2' => 'gm', 'alpha_3' => 'gmb', 'name' => 'Gambia'],
            ['id' => 268, 'alpha_2' => 'ge', 'alpha_3' => 'geo', 'name' => 'Georgia'],
            ['id' => 276, 'alpha_2' => 'de', 'alpha_3' => 'deu', 'name' => 'Germany'],
            ['id' => 288, 'alpha_2' => 'gh', 'alpha_3' => 'gha', 'name' => 'Ghana'],
            ['id' => 300, 'alpha_2' => 'gr', 'alpha_3' => 'grc', 'name' => 'Greece'],
            ['id' => 308, 'alpha_2' => 'gd', 'alpha_3' => 'grd', 'name' => 'Grenada'],
            ['id' => 320, 'alpha_2' => 'gt', 'alpha_3' => 'gtm', 'name' => 'Guatemala'],
            ['id' => 324, 'alpha_2' => 'gn', 'alpha_3' => 'gin', 'name' => 'Guinea'],
            ['id' => 624, 'alpha_2' => 'gw', 'alpha_3' => 'gnb', 'name' => 'Guinea-Bissau'],
            ['id' => 328, 'alpha_2' => 'gy', 'alpha_3' => 'guy', 'name' => 'Guyana'],
            ['id' => 332, 'alpha_2' => 'ht', 'alpha_3' => 'hti', 'name' => 'Haiti'],
            ['id' => 340, 'alpha_2' => 'hn', 'alpha_3' => 'hnd', 'name' => 'Honduras'],
            ['id' => 348, 'alpha_2' => 'hu', 'alpha_3' => 'hun', 'name' => 'Hungary'],
            ['id' => 352, 'alpha_2' => 'is', 'alpha_3' => 'isl', 'name' => 'Iceland'],
            ['id' => 356, 'alpha_2' => 'in', 'alpha_3' => 'ind', 'name' => 'India'],
            ['id' => 360, 'alpha_2' => 'id', 'alpha_3' => 'idn', 'name' => 'Indonesia'],
            ['id' => 364, 'alpha_2' => 'ir', 'alpha_3' => 'irn', 'name' => 'Iran'],
            ['id' => 368, 'alpha_2' => 'iq', 'alpha_3' => 'irq', 'name' => 'Iraq'],
            ['id' => 372, 'alpha_2' => 'ie', 'alpha_3' => 'irl', 'name' => 'Ireland'],
            ['id' => 376, 'alpha_2' => 'il', 'alpha_3' => 'isr', 'name' => 'Israel'],
            ['id' => 380, 'alpha_2' => 'it', 'alpha_3' => 'ita', 'name' => 'Italy'],
            ['id' => 388, 'alpha_2' => 'jm', 'alpha_3' => 'jam', 'name' => 'Jamaica'],
            ['id' => 392, 'alpha_2' => 'jp', 'alpha_3' => 'jpn', 'name' => 'Japan'],
            ['id' => 400, 'alpha_2' => 'jo', 'alpha_3' => 'jor', 'name' => 'Jordan'],
            ['id' => 398, 'alpha_2' => 'kz', 'alpha_3' => 'kaz', 'name' => 'Kazakhstan'],
            ['id' => 404, 'alpha_2' => 'ke', 'alpha_3' => 'ken', 'name' => 'Kenya'],
            ['id' => 296, 'alpha_2' => 'ki', 'alpha_3' => 'kir', 'name' => 'Kiribati'],
            ['id' => 408, 'alpha_2' => 'kp', 'alpha_3' => 'prk', 'name' => 'North Korea'],
            ['id' => 410, 'alpha_2' => 'kr', 'alpha_3' => 'kor', 'name' => 'South Korea'],
            ['id' => 414, 'alpha_2' => 'kw', 'alpha_3' => 'kwt', 'name' => 'Kuwait'],
            ['id' => 417, 'alpha_2' => 'kg', 'alpha_3' => 'kgz', 'name' => 'Kyrgyzstan'],
            ['id' => 418, 'alpha_2' => 'la', 'alpha_3' => 'lao', 'name' => 'Laos'],
            ['id' => 428, 'alpha_2' => 'lv', 'alpha_3' => 'lva', 'name' => 'Latvia'],
            ['id' => 422, 'alpha_2' => 'lb', 'alpha_3' => 'lbn', 'name' => 'Lebanon'],
            ['id' => 426, 'alpha_2' => 'ls', 'alpha_3' => 'lso', 'name' => 'Lesotho'],
            ['id' => 430, 'alpha_2' => 'lr', 'alpha_3' => 'lbr', 'name' => 'Liberia'],
            ['id' => 434, 'alpha_2' => 'ly', 'alpha_3' => 'lby', 'name' => 'Libya'],
            ['id' => 438, 'alpha_2' => 'li', 'alpha_3' => 'lie', 'name' => 'Liechtenstein'],
            ['id' => 440, 'alpha_2' => 'lt', 'alpha_3' => 'ltu', 'name' => 'Lithuania'],
            ['id' => 442, 'alpha_2' => 'lu', 'alpha_3' => 'lux', 'name' => 'Luxembourg'],
            ['id' => 450, 'alpha_2' => 'mg', 'alpha_3' => 'mdg', 'name' => 'Madagascar'],
            ['id' => 454, 'alpha_2' => 'mw', 'alpha_3' => 'mwi', 'name' => 'Malawi'],
            ['id' => 458, 'alpha_2' => 'my', 'alpha_3' => 'mys', 'name' => 'Malaysia'],
            ['id' => 462, 'alpha_2' => 'mv', 'alpha_3' => 'mdv', 'name' => 'Maldives'],
            ['id' => 466, 'alpha_2' => 'ml', 'alpha_3' => 'mli', 'name' => 'Mali'],
            ['id' => 470, 'alpha_2' => 'mt', 'alpha_3' => 'mlt', 'name' => 'Malta'],
            ['id' => 584, 'alpha_2' => 'mh', 'alpha_3' => 'mhl', 'name' => 'Marshall Islands'],
            ['id' => 478, 'alpha_2' => 'mr', 'alpha_3' => 'mrt', 'name' => 'Mauritania'],
            ['id' => 480, 'alpha_2' => 'mu', 'alpha_3' => 'mus', 'name' => 'Mauritius'],
            ['id' => 484, 'alpha_2' => 'mx', 'alpha_3' => 'mex', 'name' => 'Mexico'],
            ['id' => 583, 'alpha_2' => 'fm', 'alpha_3' => 'fsm', 'name' => 'Micronesia'],
            ['id' => 498, 'alpha_2' => 'md', 'alpha_3' => 'mda', 'name' => 'Moldova'],
            ['id' => 492, 'alpha_2' => 'mc', 'alpha_3' => 'mco', 'name' => 'Monaco'],
            ['id' => 496, 'alpha_2' => 'mn', 'alpha_3' => 'mng', 'name' => 'Mongolia'],
            ['id' => 499, 'alpha_2' => 'me', 'alpha_3' => 'mne', 'name' => 'Montenegro'],
            ['id' => 504, 'alpha_2' => 'ma', 'alpha_3' => 'mar', 'name' => 'Morocco'],
            ['id' => 508, 'alpha_2' => 'mz', 'alpha_3' => 'moz', 'name' => 'Mozambique'],
            ['id' => 104, 'alpha_2' => 'mm', 'alpha_3' => 'mmr', 'name' => 'Myanmar'],
            ['id' => 516, 'alpha_2' => 'na', 'alpha_3' => 'nam', 'name' => 'Namibia'],
            ['id' => 520, 'alpha_2' => 'nr', 'alpha_3' => 'nru', 'name' => 'Nauru'],
            ['id' => 524, 'alpha_2' => 'np', 'alpha_3' => 'npl', 'name' => 'Nepal'],
            ['id' => 528, 'alpha_2' => 'nl', 'alpha_3' => 'nld', 'name' => 'Netherlands'],
            ['id' => 554, 'alpha_2' => 'nz', 'alpha_3' => 'nzl', 'name' => 'New Zealand'],
            ['id' => 558, 'alpha_2' => 'ni', 'alpha_3' => 'nic', 'name' => 'Nicaragua'],
            ['id' => 562, 'alpha_2' => 'ne', 'alpha_3' => 'ner', 'name' => 'Niger'],
            ['id' => 566, 'alpha_2' => 'ng', 'alpha_3' => 'nga', 'name' => 'Nigeria'],
            ['id' => 807, 'alpha_2' => 'mk', 'alpha_3' => 'mkd', 'name' => 'North Macedonia'],
            ['id' => 578, 'alpha_2' => 'no', 'alpha_3' => 'nor', 'name' => 'Norway'],
            ['id' => 512, 'alpha_2' => 'om', 'alpha_3' => 'omn', 'name' => 'Oman'],
            ['id' => 586, 'alpha_2' => 'pk', 'alpha_3' => 'pak', 'name' => 'Pakistan'],
            ['id' => 585, 'alpha_2' => 'pw', 'alpha_3' => 'plw', 'name' => 'Palau'],
            ['id' => 591, 'alpha_2' => 'pa', 'alpha_3' => 'pan', 'name' => 'Panama'],
            ['id' => 598, 'alpha_2' => 'pg', 'alpha_3' => 'png', 'name' => 'Papua New Guinea'],
            ['id' => 600, 'alpha_2' => 'py', 'alpha_3' => 'pry', 'name' => 'Paraguay'],
            ['id' => 604, 'alpha_2' => 'pe', 'alpha_3' => 'per', 'name' => 'Peru'],
            ['id' => 608, 'alpha_2' => 'ph', 'alpha_3' => 'phl', 'name' => 'Philippines'],
            ['id' => 616, 'alpha_2' => 'pl', 'alpha_3' => 'pol', 'name' => 'Poland'],
            ['id' => 620, 'alpha_2' => 'pt', 'alpha_3' => 'prt', 'name' => 'Portugal'],
            ['id' => 634, 'alpha_2' => 'qa', 'alpha_3' => 'qat', 'name' => 'Qatar'],
            ['id' => 642, 'alpha_2' => 'ro', 'alpha_3' => 'rou', 'name' => 'Romania'],
            ['id' => 643, 'alpha_2' => 'ru', 'alpha_3' => 'rus', 'name' => 'Russia'],
            ['id' => 646, 'alpha_2' => 'rw', 'alpha_3' => 'rwa', 'name' => 'Rwanda'],
            ['id' => 659, 'alpha_2' => 'kn', 'alpha_3' => 'kna', 'name' => 'Saint Kitts and Nevis'],
            ['id' => 662, 'alpha_2' => 'lc', 'alpha_3' => 'lca', 'name' => 'Saint Lucia'],
            ['id' => 670, 'alpha_2' => 'vc', 'alpha_3' => 'vct', 'name' => 'Saint Vincent and the Grenadines'],
            ['id' => 882, 'alpha_2' => 'ws', 'alpha_3' => 'wsm', 'name' => 'Samoa'],
            ['id' => 674, 'alpha_2' => 'sm', 'alpha_3' => 'smr', 'name' => 'San Marino'],
            ['id' => 678, 'alpha_2' => 'st', 'alpha_3' => 'stp', 'name' => 'Sao Tome and Principe'],
            ['id' => 682, 'alpha_2' => 'sa', 'alpha_3' => 'sau', 'name' => 'Saudi Arabia'],
            ['id' => 686, 'alpha_2' => 'sn', 'alpha_3' => 'sen', 'name' => 'Senegal'],
            ['id' => 688, 'alpha_2' => 'rs', 'alpha_3' => 'srb', 'name' => 'Serbia'],
            ['id' => 690, 'alpha_2' => 'sc', 'alpha_3' => 'syc', 'name' => 'Seychelles'],
            ['id' => 694, 'alpha_2' => 'sl', 'alpha_3' => 'sle', 'name' => 'Sierra Leone'],
            ['id' => 702, 'alpha_2' => 'sg', 'alpha_3' => 'sgp', 'name' => 'Singapore'],
            ['id' => 703, 'alpha_2' => 'sk', 'alpha_3' => 'svk', 'name' => 'Slovakia'],
            ['id' => 705, 'alpha_2' => 'si', 'alpha_3' => 'svn', 'name' => 'Slovenia'],
            ['id' => 90, 'alpha_2' => 'sb', 'alpha_3' => 'slb', 'name' => 'Solomon Islands'],
            ['id' => 706, 'alpha_2' => 'so', 'alpha_3' => 'som', 'name' => 'Somalia'],
            ['id' => 710, 'alpha_2' => 'za', 'alpha_3' => 'zaf', 'name' => 'South Africa'],
            ['id' => 728, 'alpha_2' => 'ss', 'alpha_3' => 'ssd', 'name' => 'South Sudan'],
            ['id' => 724, 'alpha_2' => 'es', 'alpha_3' => 'esp', 'name' => 'Spain'],
            ['id' => 144, 'alpha_2' => 'lk', 'alpha_3' => 'lka', 'name' => 'Sri Lanka'],
            ['id' => 729, 'alpha_2' => 'sd', 'alpha_3' => 'sdn', 'name' => 'Sudan'],
            ['id' => 740, 'alpha_2' => 'sr', 'alpha_3' => 'sur', 'name' => 'Suriname'],
            ['id' => 752, 'alpha_2' => 'se', 'alpha_3' => 'swe', 'name' => 'Sweden'],
            ['id' => 756, 'alpha_2' => 'ch', 'alpha_3' => 'che', 'name' => 'Switzerland'],
            ['id' => 760, 'alpha_2' => 'sy', 'alpha_3' => 'syr', 'name' => 'Syria'],
            ['id' => 762, 'alpha_2' => 'tj', 'alpha_3' => 'tjk', 'name' => 'Tajikistan'],
            ['id' => 834, 'alpha_2' => 'tz', 'alpha_3' => 'tza', 'name' => 'Tanzania'],
            ['id' => 764, 'alpha_2' => 'th', 'alpha_3' => 'tha', 'name' => 'Thailand'],
            ['id' => 626, 'alpha_2' => 'tl', 'alpha_3' => 'tls', 'name' => 'Timor-Leste'],
            ['id' => 768, 'alpha_2' => 'tg', 'alpha_3' => 'tgo', 'name' => 'Togo'],
            ['id' => 776, 'alpha_2' => 'to', 'alpha_3' => 'ton', 'name' => 'Tonga'],
            ['id' => 780, 'alpha_2' => 'tt', 'alpha_3' => 'tto', 'name' => 'Trinidad and Tobago'],
            ['id' => 788, 'alpha_2' => 'tn', 'alpha_3' => 'tun', 'name' => 'Tunisia'],
            ['id' => 792, 'alpha_2' => 'tr', 'alpha_3' => 'tur', 'name' => 'Türkiye'],
            ['id' => 795, 'alpha_2' => 'tm', 'alpha_3' => 'tkm', 'name' => 'Turkmenistan'],
            ['id' => 798, 'alpha_2' => 'tv', 'alpha_3' => 'tuv', 'name' => 'Tuvalu'],
            ['id' => 800, 'alpha_2' => 'ug', 'alpha_3' => 'uga', 'name' => 'Uganda'],
            ['id' => 804, 'alpha_2' => 'ua', 'alpha_3' => 'ukr', 'name' => 'Ukraine'],
            ['id' => 784, 'alpha_2' => 'ae', 'alpha_3' => 'are', 'name' => 'United Arab Emirates'],
            ['id' => 826, 'alpha_2' => 'gb', 'alpha_3' => 'gbr', 'name' => 'United Kingdom'],
            ['id' => 840, 'alpha_2' => 'us', 'alpha_3' => 'usa', 'name' => 'United States'],
            ['id' => 858, 'alpha_2' => 'uy', 'alpha_3' => 'ury', 'name' => 'Uruguay'],
            ['id' => 860, 'alpha_2' => 'uz', 'alpha_3' => 'uzb', 'name' => 'Uzbekistan'],
            ['id' => 548, 'alpha_2' => 'vu', 'alpha_3' => 'vut', 'name' => 'Vanuatu'],
            ['id' => 862, 'alpha_2' => 've', 'alpha_3' => 'ven', 'name' => 'Venezuela'],
            ['id' => 704, 'alpha_2' => 'vn', 'alpha_3' => 'vnm', 'name' => 'Vietnam'],
            ['id' => 887, 'alpha_2' => 'ye', 'alpha_3' => 'yem', 'name' => 'Yemen'],
            ['id' => 894, 'alpha_2' => 'zm', 'alpha_3' => 'zmb', 'name' => 'Zambia'],
            ['id' => 716, 'alpha_2' => 'zw', 'alpha_3' => 'zwe', 'name' => 'Zimbabwe'],
        ];

        foreach ($countries as $country) {
            MasterCountry::create([
                'alpha_2' => $country['alpha_2'],
                'alpha_3' => $country['alpha_3'],
                'name' => $country['name'],
                'phone_code' => $phoneCodes[$country['alpha_2']] ?? null,
            ]);
        }

        $this->command->info('Seeded ' . count($countries) . ' countries with phone codes.');
    }
}
