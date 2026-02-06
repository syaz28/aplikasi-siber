/**
 * Person Data Parser & Extractor Helper
 * 
 * Reusable functions for parsing and extracting person data from text
 * Can be used across modules: Laporan, Orang, Tersangka, Korban, Pelapor, etc.
 * 
 * @module usePersonDataParser
 */

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Format string to UPPERCASE
 * @param {string} str - String to format
 * @returns {string} - Uppercase trimmed string
 */
export const toUpperCase = (str) => {
    if (!str) return '';
    return str.toUpperCase().trim();
};

/**
 * Clean and normalize text for parsing (remove trailing punctuation)
 * @param {string} str - String to clean
 * @returns {string} - Cleaned string
 */
export const cleanText = (str) => {
    if (!str) return '';
    return str.replace(/[,\.\:]+$/, '').trim();
};

/**
 * Format name properly (Title Case)
 * @param {string} str - Name string
 * @returns {string} - Title case formatted name
 */
export const formatName = (str) => {
    if (!str) return '';
    return str.toLowerCase()
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
        .trim();
};

// ============================================
// DATA NORMALIZATION CONSTANTS
// ============================================

/**
 * Fields that should be UPPERCASE for consistency
 * These are identity/structured data fields
 */
export const UPPERCASE_FIELDS = [
    // Person identity
    'nik', 'nama', 'tempat_lahir', 'jenis_kelamin', 'pekerjaan', 'pendidikan',
    'agama', 'kewarganegaraan', 'negara',
    // Address
    'detail_alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten', 'provinsi',
    // Identitas tersangka nilai (phone numbers, emails, usernames - for consistency)
    'nilai',
];

/**
 * Fields that should PRESERVE original case (narrative/descriptive)
 * These contain user-written descriptions that need their format preserved
 */
export const PRESERVE_CASE_FIELDS = [
    'modus', 'catatan', 'kronologi', 'uraian', 'keterangan', 'deskripsi',
];

/**
 * Normalize a single field value based on field name
 * @param {string} fieldName - Name of the field
 * @param {string} value - Value to normalize
 * @returns {string} - Normalized value
 */
export const normalizeFieldValue = (fieldName, value) => {
    if (!value || typeof value !== 'string') return value;
    
    // Check if field should preserve case
    const lowerFieldName = fieldName.toLowerCase();
    if (PRESERVE_CASE_FIELDS.some(f => lowerFieldName.includes(f))) {
        return value.trim();
    }
    
    // Check if field should be uppercase
    if (UPPERCASE_FIELDS.some(f => lowerFieldName.includes(f))) {
        return value.toUpperCase().trim();
    }
    
    // Default: preserve original
    return value.trim();
};

/**
 * Normalize person data object (pelapor, korban, tersangka)
 * Applies UPPERCASE to identity fields, preserves case for narrative fields
 * @param {object} person - Person data object
 * @returns {object} - Normalized person data
 */
export const normalizePersonData = (person) => {
    if (!person || typeof person !== 'object') return person;
    
    const normalized = { ...person };
    
    // Fields to uppercase
    const uppercaseFields = [
        'nik', 'nama', 'tempat_lahir', 'jenis_kelamin', 'pekerjaan', 
        'pendidikan', 'agama', 'kewarganegaraan', 'negara'
    ];
    
    // Process direct fields
    for (const field of uppercaseFields) {
        if (normalized[field] && typeof normalized[field] === 'string') {
            normalized[field] = normalized[field].toUpperCase().trim();
        }
    }
    
    // Process alamat_ktp if exists
    if (normalized.alamat_ktp && typeof normalized.alamat_ktp === 'object') {
        const alamat = { ...normalized.alamat_ktp };
        if (alamat.detail_alamat) {
            alamat.detail_alamat = alamat.detail_alamat.toUpperCase().trim();
        }
        normalized.alamat_ktp = alamat;
    }
    
    // Process alamat_domisili if exists
    if (normalized.alamat_domisili && typeof normalized.alamat_domisili === 'object') {
        const alamat = { ...normalized.alamat_domisili };
        if (alamat.detail_alamat) {
            alamat.detail_alamat = alamat.detail_alamat.toUpperCase().trim();
        }
        normalized.alamat_domisili = alamat;
    }
    
    // PRESERVE catatan field (narrative)
    // No change needed, it stays as-is
    
    return normalized;
};

/**
 * Normalize laporan form data before submission
 * @param {object} formData - Complete form data
 * @returns {object} - Normalized form data ready for submission
 */
export const normalizeLaporanFormData = (formData) => {
    if (!formData || typeof formData !== 'object') return formData;
    
    const normalized = { ...formData };
    
    // Normalize pelapor
    if (normalized.pelapor) {
        normalized.pelapor = normalizePersonData(normalized.pelapor);
    }
    
    // Normalize korban array
    if (Array.isArray(normalized.korban)) {
        normalized.korban = normalized.korban.map(k => normalizePersonData(k));
    }
    
    // Normalize tersangka array (but preserve catatan)
    if (Array.isArray(normalized.tersangka)) {
        normalized.tersangka = normalized.tersangka.map(t => {
            const normalizedT = { ...t };
            // Preserve catatan as-is (narrative field)
            // Normalize identitas values
            if (Array.isArray(normalizedT.identitas)) {
                normalizedT.identitas = normalizedT.identitas.map(id => ({
                    ...id,
                    nilai: id.nilai ? id.nilai.toUpperCase().trim() : '',
                    // platform stays as-is (it's a dropdown value)
                }));
            }
            return normalizedT;
        });
    }
    
    // PRESERVE modus and catatan (narrative fields) - no uppercase
    // normalized.modus stays as-is
    // normalized.catatan stays as-is
    
    return normalized;
};

// ============================================
// WILAYAH PARSING FUNCTIONS
// ============================================

/**
 * Parse and find Provinsi from dropdown list
 * @param {string} searchName - Name to search (e.g., "jawa tengah", "JAWA TENGAH")
 * @param {array} provinsiList - List of provinsi with {kode, nama}
 * @returns {object|null} - Found wilayah object with {kode, nama} or null
 */
export const parseProvinsi = (searchName, provinsiList) => {
    if (!searchName || !provinsiList?.length) return null;
    const search = searchName.toUpperCase().trim();
    
    // Exact match
    let found = provinsiList.find(w => w.nama.toUpperCase() === search);
    if (found) return found;
    
    // Contains match
    found = provinsiList.find(w => 
        w.nama.toUpperCase().includes(search) || search.includes(w.nama.toUpperCase())
    );
    return found || null;
};

/**
 * Parse and find Kabupaten/Kota from dropdown list
 * Automatically adds "KOTA " prefix if plain city name is provided
 * @param {string} searchName - Name to search (e.g., "semarang", "KOTA SEMARANG", "KAB. SEMARANG")
 * @param {array} kabupatenList - List of kabupaten/kota with {kode, nama}
 * @returns {object|null} - Found wilayah object with {kode, nama} or null
 */
export const parseKabupatenKota = (searchName, kabupatenList) => {
    if (!searchName || !kabupatenList?.length) return null;
    const search = searchName.toUpperCase().trim();
    
    // Priority 1: Exact match
    let found = kabupatenList.find(w => w.nama.toUpperCase() === search);
    if (found) return found;
    
    // Priority 2: Try with "KOTA " prefix (default for city names without prefix)
    found = kabupatenList.find(w => w.nama.toUpperCase() === 'KOTA ' + search);
    if (found) return found;
    
    // Priority 3: Try with "KAB. " prefix
    found = kabupatenList.find(w => w.nama.toUpperCase() === 'KAB. ' + search);
    if (found) return found;
    
    // Priority 4: Try with "KABUPATEN " prefix
    found = kabupatenList.find(w => w.nama.toUpperCase() === 'KABUPATEN ' + search);
    if (found) return found;
    
    // Priority 5: Contains match
    found = kabupatenList.find(w => {
        const wName = w.nama.toUpperCase();
        return wName.includes(search) || search.includes(wName);
    });
    if (found) return found;
    
    // Priority 6: Match without prefix (clean both sides)
    const cleanSearch = search.replace(/^(KOTA|KAB\.?|KABUPATEN)\s*/i, '');
    found = kabupatenList.find(w => {
        const cleanName = w.nama.toUpperCase().replace(/^(KOTA|KAB\.?|KABUPATEN)\s*/i, '');
        return cleanName === cleanSearch;
    });
    
    return found || null;
};

/**
 * Parse and find Kecamatan from dropdown list
 * @param {string} searchName - Name to search (e.g., "semarang utara", "SEMARANG UTARA")
 * @param {array} kecamatanList - List of kecamatan with {kode, nama}
 * @returns {object|null} - Found wilayah object with {kode, nama} or null
 */
export const parseKecamatan = (searchName, kecamatanList) => {
    if (!searchName || !kecamatanList?.length) return null;
    const search = searchName.toUpperCase().trim();
    
    // Exact match
    let found = kecamatanList.find(w => w.nama.toUpperCase() === search);
    if (found) return found;
    
    // Contains match
    found = kecamatanList.find(w => 
        w.nama.toUpperCase().includes(search) || search.includes(w.nama.toUpperCase())
    );
    if (found) return found;
    
    // Word-based match
    const searchWords = search.split(' ').filter(w => w.length > 2);
    for (const word of searchWords) {
        found = kecamatanList.find(w => w.nama.toUpperCase().includes(word));
        if (found) return found;
    }
    
    return null;
};

/**
 * Parse and find Kelurahan from dropdown list
 * @param {string} searchName - Name to search (e.g., "jagalan", "JAGALAN")
 * @param {array} kelurahanList - List of kelurahan with {kode, nama}
 * @returns {object|null} - Found wilayah object with {kode, nama} or null
 */
export const parseKelurahan = (searchName, kelurahanList) => {
    if (!searchName || !kelurahanList?.length) return null;
    const search = searchName.toUpperCase().trim();
    
    // Exact match
    let found = kelurahanList.find(w => w.nama.toUpperCase() === search);
    if (found) return found;
    
    // Contains match
    found = kelurahanList.find(w => 
        w.nama.toUpperCase().includes(search) || search.includes(w.nama.toUpperCase())
    );
    
    return found || null;
};

// ============================================
// TEXT EXTRACTION FUNCTIONS
// ============================================

/**
 * Extract Provinsi name from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted provinsi name (UPPERCASE) or null
 */
export const extractProvinsiFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    // Pattern 1: Explicit "provinsi" or "prov." label
    const provMatch = lowerText.match(/prov(?:insi)?[\.\s]*[\.:=\-]?\s*([a-z\s]+?)(?=\s*[,\.]?\s*(?:indonesia|$|\n))/);
    if (provMatch) {
        return toUpperCase(cleanText(provMatch[1]));
    }
    
    // Pattern 2: Direct province name detection
    const directMatch = lowerText.match(/(jawa\s*tengah|jawa\s*timur|jawa\s*barat|d\.?k\.?i\.?\s*jakarta|dki\s*jakarta|d\.?i\.?\s*yogyakarta|diy|yogyakarta|banten|sumatera\s*utara|sumatera\s*selatan|sumatera\s*barat|kalimantan\s*utara|kalimantan\s*selatan|kalimantan\s*timur|kalimantan\s*barat|kalimantan\s*tengah|sulawesi\s*utara|sulawesi\s*selatan|sulawesi\s*tengah|sulawesi\s*tenggara|sulawesi\s*barat|bali|nusa\s*tenggara\s*barat|nusa\s*tenggara\s*timur|ntb|ntt|maluku|maluku\s*utara|papua|papua\s*barat|aceh|riau|jambi|bengkulu|lampung|kepulauan\s*riau|kepulauan\s*bangka\s*belitung|gorontalo)(?=\s*[,\.]?\s*(?:$|\n|indonesia))/);
    if (directMatch) {
        return toUpperCase(cleanText(directMatch[1].replace(/\s+/g, ' ')));
    }
    
    return null;
};

/**
 * Extract Kabupaten/Kota name from raw text
 * Returns formatted name with KOTA/KAB. prefix
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted kabupaten/kota name (UPPERCASE with prefix) or null
 */
export const extractKabupatenKotaFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    // Pattern 1: Explicit "kota" prefix
    const kotaMatch = lowerText.match(/kota[\.\s]*[\.:=\-]?\s*([a-z\s]+?)(?=\s*[,\.]?\s*(?:prov(?:insi)?|jawa|sumatera|kalimantan|sulawesi|bali|papua|nusa|maluku|kepulauan|$|\n))/);
    if (kotaMatch) {
        return 'KOTA ' + toUpperCase(cleanText(kotaMatch[1]));
    }
    
    // Pattern 2: Explicit "kabupaten" or "kab." prefix
    const kabMatch = lowerText.match(/kab(?:upaten)?[\.\s]*[\.:=\-]?\s*([a-z\s]+?)(?=\s*[,\.]?\s*(?:prov(?:insi)?|jawa|sumatera|kalimantan|sulawesi|bali|papua|nusa|maluku|kepulauan|$|\n))/);
    if (kabMatch) {
        return 'KAB. ' + toUpperCase(cleanText(kabMatch[1]));
    }
    
    // Pattern 3: Standalone city name after kecamatan (assume KOTA if no prefix)
    const standaloneMatch = lowerText.match(/kec(?:amatan)?[\.\s]*[\.:=\-]?\s*[a-z\s]+?[,\.\s]+([a-z\s]+?)(?=\s*[,\.]?\s*(?:prov(?:insi)?|jawa|sumatera|kalimantan|sulawesi|bali|papua|nusa|maluku|kepulauan|$|\n))/);
    if (standaloneMatch) {
        const cityName = toUpperCase(cleanText(standaloneMatch[1]));
        // Skip if it looks like a province name
        if (cityName && !cityName.match(/^(JAWA|SUMATERA|KALIMANTAN|SULAWESI|BALI|PAPUA|NUSA|MALUKU|KEPULAUAN|ACEH|RIAU|JAMBI|BENGKULU|LAMPUNG|BANTEN|GORONTALO)/)) {
            return 'KOTA ' + cityName;
        }
    }
    
    return null;
};

/**
 * Extract Kecamatan name from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted kecamatan name (UPPERCASE) or null
 */
export const extractKecamatanFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    const kecMatch = lowerText.match(/kec(?:amatan)?[\.\s]*[\.:=\-]?\s*([a-z\s]+?)(?=\s*[,\.]?\s*(?:kab(?:upaten)?|kota|prov(?:insi)?|jawa|sumatera|kalimantan|sulawesi|bali|papua|nusa|maluku|kepulauan|$|\n))/);
    if (kecMatch) {
        return toUpperCase(cleanText(kecMatch[1]));
    }
    
    return null;
};

/**
 * Extract Kelurahan/Desa name from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted kelurahan name (UPPERCASE) or null
 */
export const extractKelurahanFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    const kelMatch = lowerText.match(/(?:kel(?:urahan)?|desa)[\.\s]*[\.:=\-]?\s*([a-z\s]+?)(?=\s*[,\.]?\s*(?:kec(?:amatan)?|kab(?:upaten)?|kota|prov|rt|rw|$|\n))/);
    if (kelMatch) {
        return toUpperCase(cleanText(kelMatch[1]));
    }
    
    return null;
};

/**
 * Extract RT/RW from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Formatted RT/RW string or null
 */
export const extractRtRwFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    const rtRwMatch = lowerText.match(/rt\s*[\.:=\-\/]?\s*(\d{1,3})\s*[\/\-,\s]*rw\s*[\.:=\-\/]?\s*(\d{1,3})/);
    if (rtRwMatch) {
        return `RT ${rtRwMatch[1].padStart(3, '0')} / RW ${rtRwMatch[2].padStart(3, '0')}`;
    }
    
    return null;
};

/**
 * Extract NIK (16 digits) from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted NIK or null
 */
export const extractNikFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    // With label
    const nikMatch = lowerText.match(/nik\s*[\.:=\-]?\s*(\d{16})/);
    if (nikMatch) return nikMatch[1];
    
    // Standalone 16 digits
    const nikStandalone = text.match(/\b(\d{16})\b/);
    if (nikStandalone) return nikStandalone[1];
    
    return null;
};

/**
 * Extract Nama from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted name (UPPERCASE) or null
 */
export const extractNamaFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    const namaMatch = lowerText.match(/nama\s*[\.:=\-]?\s*([a-z\s']+?)(?=\s*(?:tempat|ttl|tanggal|lahir|nik|jenis|alamat|pekerjaan|agama|$|\n))/);
    if (namaMatch) {
        return toUpperCase(cleanText(namaMatch[1]));
    }
    
    return null;
};

/**
 * Extract Jenis Kelamin from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - "LAKI-LAKI" or "PEREMPUAN" or null (UPPERCASE)
 */
export const extractJenisKelaminFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    const jkMatch = lowerText.match(/jenis\s*kelamin\s*[\.:=\-]?\s*(laki[\-\s]?laki|perempuan|pria|wanita|l|p)/);
    if (jkMatch) {
        const jk = jkMatch[1];
        return (jk === 'l' || jk.includes('laki') || jk === 'pria') ? 'LAKI-LAKI' : 'PEREMPUAN';
    }
    
    return null;
};

/**
 * Extract Pekerjaan from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted pekerjaan (UPPERCASE) or null
 */
export const extractPekerjaanFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    const pekerjaanMatch = lowerText.match(/pekerjaan\s*[\.:=\-]?\s*([a-z\s\/\-]+?)(?=\s*(?:alamat|agama|status|kewarganegaraan|no|telepon|hp|\n|$))/);
    if (pekerjaanMatch) {
        return toUpperCase(cleanText(pekerjaanMatch[1]));
    }
    
    return null;
};

/**
 * Extract Telepon/HP from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted phone number or null
 */
export const extractTeleponFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    // With label
    const teleponMatch = lowerText.match(/(?:telepon|hp|no\.?\s*hp|handphone|phone|nomor\s*(?:hp|telepon))\s*[\.:=\-]?\s*([\d\-\s\+]+)/);
    if (teleponMatch) {
        return teleponMatch[1].replace(/[\s\-]/g, '');
    }
    
    // Standalone phone pattern
    const phoneMatch = text.match(/\b(0\d{9,12}|62\d{9,12}|\+62\d{9,12})\b/);
    if (phoneMatch) return phoneMatch[1].replace(/[\s\-]/g, '');
    
    return null;
};

/**
 * Extract Alamat detail from raw text (without RT/RW, kelurahan, kecamatan, kota, provinsi)
 * Only returns the street/building/detail portion
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted address detail (UPPERCASE) or null
 */
export const extractAlamatFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    // First, try to extract everything after "alamat:" up to wilayah components
    const alamatMatch = lowerText.match(/alamat\s*[\.:=\-]?\s*(.+?)(?=\s*(?:kel(?:urahan)?[\.\s,]|desa[\.\s,]|kec(?:amatan)?[\.\s,]|kab(?:upaten)?[\.\s,]|kota[\.\s,]|prov(?:insi)?[\.\s,]|pekerjaan|agama|status|no\s*hp|nohp|\n\s*\w+\s*[\.:=\-]|$))/is);
    
    if (alamatMatch) {
        let addr = alamatMatch[1].replace(/[\n\r]+/g, ' ').trim();
        
        // Remove RT/RW pattern from the beginning or anywhere in the address
        // Because RT/RW is extracted separately
        addr = addr.replace(/^rt\s*[\.:=\-\/]?\s*\d{1,3}\s*[\/\-,\s]*rw\s*[\.:=\-\/]?\s*\d{1,3}\s*[,\.\s]*/i, '');
        addr = addr.replace(/\s*,?\s*rt\s*[\.:=\-\/]?\s*\d{1,3}\s*[\/\-,\s]*rw\s*[\.:=\-\/]?\s*\d{1,3}\s*$/i, '');
        
        // Clean up any trailing commas or dots
        addr = addr.replace(/^[,\.\s]+|[,\.\s]+$/g, '').trim();
        
        if (addr) {
            return toUpperCase(cleanText(addr));
        }
    }
    
    return null;
};

/**
 * Parse Indonesian date formats to YYYY-MM-DD
 * @param {string} dateStr - Date string to parse
 * @returns {string|null} - Formatted date (YYYY-MM-DD) or null
 */
export const parseIndonesianDate = (dateStr) => {
    if (!dateStr) return null;
    
    const months = {
        'januari': '01', 'februari': '02', 'maret': '03', 'april': '04',
        'mei': '05', 'juni': '06', 'juli': '07', 'agustus': '08',
        'september': '09', 'oktober': '10', 'november': '11', 'desember': '12',
        'jan': '01', 'feb': '02', 'mar': '03', 'apr': '04',
        'may': '05', 'jun': '06', 'jul': '07', 'aug': '08', 'ags': '08',
        'sep': '09', 'oct': '10', 'okt': '10', 'nov': '11', 'dec': '12', 'des': '12'
    };
    
    // Try DD-MM-YYYY or DD/MM/YYYY
    let match = dateStr.match(/(\d{1,2})[\-\/\s](\d{1,2})[\-\/\s](\d{2,4})/);
    if (match) {
        let [, day, month, year] = match;
        if (year.length === 2) year = (parseInt(year) > 50 ? '19' : '20') + year;
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }
    
    // Try DD Month YYYY
    match = dateStr.match(/(\d{1,2})[\s\-]([A-Za-z]+)[\s\-](\d{2,4})/i);
    if (match) {
        let [, day, monthName, year] = match;
        const month = months[monthName.toLowerCase()];
        if (month) {
            if (year.length === 2) year = (parseInt(year) > 50 ? '19' : '20') + year;
            return `${year}-${month}-${day.padStart(2, '0')}`;
        }
    }
    
    return null;
};

/**
 * Extract Tempat Lahir from raw text
 * @param {string} text - Text to parse
 * @param {array} kabupatenList - Optional kabupaten list for matching
 * @returns {string|null} - Extracted tempat lahir (UPPERCASE, matched with dropdown if available) or null
 */
export const extractTempatLahirFromText = (text, kabupatenList = null) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    // Try TTL format first
    let tempatLahir = null;
    const ttlMatch = lowerText.match(/(?:tempat\s*[\/,]?\s*(?:tanggal\s*)?lahir|ttl)\s*[\.:=\-]?\s*([a-z\s]+?)[,\s]+\d/);
    if (ttlMatch) {
        tempatLahir = cleanText(ttlMatch[1]);
    }
    
    // Try standalone "tempat lahir" format
    if (!tempatLahir) {
        const tempatMatch = lowerText.match(/tempat\s*lahir\s*[\.:=\-]?\s*([a-z\s]+?)(?=\s*(?:tanggal|,|\n|$))/);
        if (tempatMatch) {
            tempatLahir = cleanText(tempatMatch[1]);
        }
    }
    
    if (!tempatLahir) return null;
    
    // If kabupaten list is provided, try to find matching entry
    if (kabupatenList?.length) {
        const found = parseKabupatenKota(tempatLahir, kabupatenList);
        if (found) {
            return found.nama;
        }
    }
    
    // Fallback to uppercase version
    return toUpperCase(tempatLahir);
};

/**
 * Extract Tanggal Lahir from raw text
 * @param {string} text - Text to parse
 * @returns {string|null} - Extracted tanggal lahir (YYYY-MM-DD) or null
 */
export const extractTanggalLahirFromText = (text) => {
    if (!text) return null;
    const lowerText = text.toLowerCase();
    
    // TTL format
    const ttlMatch = lowerText.match(/(?:tempat\s*[\/,]?\s*(?:tanggal\s*)?lahir|ttl)\s*[\.:=\-]?\s*[a-z\s]+?[,\s]+(\d{1,2}[\-\/\s](?:\d{1,2}|[a-z]+)[\-\/\s]\d{2,4})/);
    if (ttlMatch) {
        return parseIndonesianDate(ttlMatch[1].trim());
    }
    
    // Standalone tanggal lahir
    const tglMatch = lowerText.match(/tanggal\s*lahir\s*[\.:=\-]?\s*(\d{1,2}[\-\/\s](?:\d{1,2}|[a-z]+)[\-\/\s]\d{2,4})/);
    if (tglMatch) {
        return parseIndonesianDate(tglMatch[1].trim());
    }
    
    return null;
};

// ============================================
// COMPLETE EXTRACTION FUNCTION
// ============================================

/**
 * Extract all person data from raw text
 * @param {string} rawText - Raw text to parse
 * @param {object} options - Optional configurations
 * @param {array} options.kabupatenList - Kabupaten list for tempat lahir matching
 * @returns {object} - Extracted person data object
 */
export const extractPersonDataFromText = (rawText, options = {}) => {
    if (!rawText) return null;
    
    const extracted = {
        nik: extractNikFromText(rawText),
        nama: extractNamaFromText(rawText),
        tempat_lahir: extractTempatLahirFromText(rawText, options.kabupatenList),
        tanggal_lahir: extractTanggalLahirFromText(rawText),
        jenis_kelamin: extractJenisKelaminFromText(rawText),
        pekerjaan: extractPekerjaanFromText(rawText),
        telepon: extractTeleponFromText(rawText),
        alamat: extractAlamatFromText(rawText),
        rt_rw: extractRtRwFromText(rawText),
        kelurahan: extractKelurahanFromText(rawText),
        kecamatan: extractKecamatanFromText(rawText),
        kabupaten: extractKabupatenKotaFromText(rawText),
        provinsi: extractProvinsiFromText(rawText),
    };
    
    // Check if any data was extracted
    const hasData = Object.values(extracted).some(v => v !== null);
    
    return hasData ? extracted : null;
};

// ============================================
// COMPOSABLE HOOK
// ============================================

/**
 * Vue Composable for person data parsing
 * Provides all parsing and extraction functions
 * 
 * @example
 * import { usePersonDataParser } from '@/Composables/usePersonDataParser';
 * 
 * const { extractPersonDataFromText, parseKabupatenKota } = usePersonDataParser();
 * const data = extractPersonDataFromText(rawText, { kabupatenList: masterData.kabupaten_all });
 * const kab = parseKabupatenKota('semarang', kabupatenList);
 */
export const usePersonDataParser = () => {
    return {
        // Helper functions
        toUpperCase,
        cleanText,
        formatName,
        
        // Wilayah parsing
        parseProvinsi,
        parseKabupatenKota,
        parseKecamatan,
        parseKelurahan,
        
        // Text extraction
        extractProvinsiFromText,
        extractKabupatenKotaFromText,
        extractKecamatanFromText,
        extractKelurahanFromText,
        extractRtRwFromText,
        extractNikFromText,
        extractNamaFromText,
        extractJenisKelaminFromText,
        extractPekerjaanFromText,
        extractTeleponFromText,
        extractAlamatFromText,
        extractTempatLahirFromText,
        extractTanggalLahirFromText,
        parseIndonesianDate,
        
        // Complete extraction
        extractPersonDataFromText,
        
        // Data normalization
        UPPERCASE_FIELDS,
        PRESERVE_CASE_FIELDS,
        normalizeFieldValue,
        normalizePersonData,
        normalizeLaporanFormData,
    };
};

export default usePersonDataParser;
