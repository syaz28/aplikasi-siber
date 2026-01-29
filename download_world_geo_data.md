# Download World Geographic Data (Similar to wilayah.sql)

## 🌍 BEST OPTION: GeoNames.org

### Step 1: Download Files
```bash
# Countries master data
wget http://download.geonames.org/export/dump/countryInfo.txt

# Admin Level 1 (Provinces/States)
wget http://download.geonames.org/export/dump/admin1CodesASCII.txt

# Admin Level 2 (Districts/Kabupaten)
wget http://download.geonames.org/export/dump/admin2Codes.txt

# All cities (filtered by population)
wget http://download.geonames.org/export/dump/cities15000.zip
unzip cities15000.zip

# OR download specific country (e.g., Indonesia)
wget http://download.geonames.org/export/dump/ID.zip
unzip ID.zip
```

### Step 2: Import to MySQL

**Create Tables:**
```sql
-- Countries
CREATE TABLE world_countries (
    iso CHAR(2) PRIMARY KEY,
    iso3 CHAR(3),
    iso_numeric INT,
    fips CHAR(2),
    country VARCHAR(200),
    capital VARCHAR(200),
    area_sq_km INT,
    population BIGINT,
    continent CHAR(2),
    tld VARCHAR(10),
    currency_code CHAR(3),
    currency_name VARCHAR(50),
    phone VARCHAR(20),
    postal_code_format VARCHAR(100),
    postal_code_regex VARCHAR(200),
    languages VARCHAR(200),
    geonameid INT,
    neighbours VARCHAR(100),
    equivalent_fips_code VARCHAR(10)
);

-- Admin1 (Provinces/States)
CREATE TABLE world_admin1 (
    code VARCHAR(20) PRIMARY KEY,
    name VARCHAR(200),
    ascii_name VARCHAR(200),
    geonameid INT,
    country_code CHAR(2),
    FOREIGN KEY (country_code) REFERENCES world_countries(iso)
);

-- Admin2 (Districts/Kabupaten)
CREATE TABLE world_admin2 (
    code VARCHAR(80) PRIMARY KEY,
    name VARCHAR(200),
    ascii_name VARCHAR(200),
    geonameid INT,
    admin1_code VARCHAR(20),
    country_code CHAR(2),
    FOREIGN KEY (country_code) REFERENCES world_countries(iso)
);

-- Cities/Places
CREATE TABLE world_geonames (
    geonameid INT PRIMARY KEY,
    name VARCHAR(200),
    ascii_name VARCHAR(200),
    alternate_names TEXT,
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    feature_class CHAR(1),
    feature_code VARCHAR(10),
    country_code CHAR(2),
    cc2 VARCHAR(200),
    admin1_code VARCHAR(20),
    admin2_code VARCHAR(80),
    admin3_code VARCHAR(20),
    admin4_code VARCHAR(20),
    population BIGINT,
    elevation INT,
    dem INT,
    timezone VARCHAR(40),
    modification_date DATE,
    
    FOREIGN KEY (country_code) REFERENCES world_countries(iso),
    INDEX idx_country (country_code),
    INDEX idx_admin1 (admin1_code),
    INDEX idx_admin2 (admin2_code),
    INDEX idx_name (name)
);
```

**Import Data:**
```sql
-- Import countries (skip header lines with #)
LOAD DATA INFILE 'countryInfo.txt' 
INTO TABLE world_countries
FIELDS TERMINATED BY '\t'
IGNORE 51 LINES;  -- Skip header

-- Import admin1
LOAD DATA INFILE 'admin1CodesASCII.txt'
INTO TABLE world_admin1
FIELDS TERMINATED BY '\t';

-- Import admin2
LOAD DATA INFILE 'admin2Codes.txt'
INTO TABLE world_admin2
FIELDS TERMINATED BY '\t';

-- Import cities (from cities15000.txt or ID.txt)
LOAD DATA INFILE 'cities15000.txt'
INTO TABLE world_geonames
FIELDS TERMINATED BY '\t';
```

### Step 3: Query Examples

**Get all countries:**
```sql
SELECT iso, country, capital, population 
FROM world_countries 
ORDER BY country;
```

**Get provinces/states of a country (e.g., USA):**
```sql
SELECT code, name, ascii_name
FROM world_admin1
WHERE country_code = 'US'
ORDER BY name;
```

**Get cities in a province (e.g., California):**
```sql
SELECT name, population, latitude, longitude
FROM world_geonames
WHERE country_code = 'US' 
  AND admin1_code = 'CA'
  AND feature_code = 'PPL'  -- Populated Place
ORDER BY population DESC
LIMIT 20;
```

**Hierarchical query (Country → State → City):**
```sql
SELECT 
    c.country,
    a1.name AS province,
    a2.name AS district,
    g.name AS city,
    g.population
FROM world_geonames g
LEFT JOIN world_countries c ON g.country_code = c.iso
LEFT JOIN world_admin1 a1 ON g.admin1_code = a1.code
LEFT JOIN world_admin2 a2 ON g.admin2_code = a2.code
WHERE g.country_code = 'ID'  -- Indonesia
  AND g.feature_code = 'PPL'
ORDER BY g.population DESC
LIMIT 50;
```

## 🎯 FOR YOUR SIBER JATENG PROJECT:

### Use Case 1: Dropdown Negara Asal Tersangka
```sql
-- Add to tersangka table
ALTER TABLE tersangka ADD COLUMN negara_asal CHAR(2);
ALTER TABLE tersangka ADD FOREIGN KEY (negara_asal) 
    REFERENCES world_countries(iso);
```

### Use Case 2: Track International Cyber Crime
```sql
-- Add to laporan table
ALTER TABLE laporan ADD COLUMN negara_kejadian CHAR(2) DEFAULT 'ID';
ALTER TABLE laporan ADD COLUMN kota_kejadian_luar_negeri VARCHAR(200);
```

### Use Case 3: Dashboard Statistik per Negara
```sql
SELECT 
    c.country,
    COUNT(l.id) AS total_laporan,
    SUM(k.kerugian_nominal) AS total_kerugian
FROM laporan l
JOIN tersangka t ON l.id = t.laporan_id
JOIN world_countries c ON t.negara_asal = c.iso
JOIN korban k ON l.id = k.laporan_id
GROUP BY c.country
ORDER BY total_laporan DESC;
```

## 📚 RESOURCES:

- **GeoNames:** https://www.geonames.org/
- **Documentation:** https://www.geonames.org/export/
- **Forum:** https://forum.geonames.org/
- **License:** Creative Commons Attribution 4.0

## ⚠️ NOTES:

1. **File Size:** `allCountries.txt` = 1.5GB unzipped!
2. **Import Time:** Bisa 10-30 menit tergantung server
3. **Encoding:** UTF-8 (pastikan MySQL charset utf8mb4)
4. **Updates:** Download ulang setiap bulan untuk data terbaru
5. **Alternative:** Gunakan `cities15000.zip` (cities with 15K+ pop) untuk data lebih ringan

## 🚀 QUICK START (Light Version):

Kalau mau coba dulu dengan data ringan:
```bash
# Download only countries + major cities (15K+ population)
wget http://download.geonames.org/export/dump/countryInfo.txt
wget http://download.geonames.org/export/dump/cities15000.zip
unzip cities15000.zip

# Import (~47K cities, ~15MB)
# Jauh lebih ringan dari 11M entries!
```
