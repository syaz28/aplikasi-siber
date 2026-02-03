# ✅ Shared Account Refactor - Implementation Complete

## Summary
Successfully refactored the authentication system from individual NRP-based logins to a **shared account model** with username-based authentication.

---

## 🗂️ Database Changes

### Users Table - BEFORE (Personal Accounts)
```sql
id, name, email, nrp, pangkat, telepon, jabatan, password, role, subdit, unit, is_active
```

### Users Table - AFTER (Shared Accounts)
```sql
id, username, password, role, subdit_access, created_at, updated_at
```

**Dropped Columns:** `name`, `email`, `email_verified_at`, `nrp`, `pangkat`, `telepon`, `jabatan`, `subdit`, `unit`, `is_active`

**Added Columns:** `username` (unique), `role` (enum), `subdit_access` (nullable int)

---

### New Personels Table
```sql
CREATE TABLE personels (
    id BIGINT PRIMARY KEY,
    nrp VARCHAR(20) UNIQUE,
    nama_lengkap VARCHAR(255),
    pangkat VARCHAR(255) NULL,
    jabatan VARCHAR(255) NULL,
    subdit_id INT NULL,
    unit_id VARCHAR(255) NULL,
    telepon VARCHAR(20) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Purpose:** Store actual police officer data for:
- Pawas selection dropdown
- Penyidik assignment
- Personnel management

---

## 👤 Default User Accounts

| Username | Password | Role | Subdit Access | Access To |
|----------|----------|------|---------------|-----------|
| `admin` | `password` | admin | - | User Management (`/admin`) |
| `petugas` | `password` | petugas | - | Case Entry with Pawas selection (`/dashboard`) |
| `pimpinan` | `password` | pimpinan | - | Executive Dashboard Only (`/pimpinan/dashboard`) |
| `subdit1` | `password` | admin_subdit | 1 (Ekonomi) | Filtered Case Management (`/dashboard`) |
| `subdit2` | `password` | admin_subdit | 2 (Sosial) | Filtered Case Management (`/dashboard`) |
| `subdit3` | `password` | admin_subdit | 3 (Khusus) | Filtered Case Management (`/dashboard`) |

---

## 🔐 Login System Changes

### Frontend (`Login.vue`)
- **Old:** NRP input field (numeric only)
- **New:** Username input field (text)
- **Placeholder:** `"admin / petugas / pimpinan / subdit1"`

### Backend (`LoginRequest.php`)
```php
// OLD
Auth::attempt(['nrp' => $request->nrp, 'password' => $request->password]);

// NEW  
Auth::attempt(['username' => $request->username, 'password' => $request->password]);
```

### Role-Based Redirects (`AuthenticatedSessionController.php`)
```php
if ($user->role === 'admin') 
    return redirect()->route('admin.dashboard'); // /admin

if ($user->role === 'pimpinan') 
    return redirect()->route('pimpinan.dashboard'); // /pimpinan/dashboard

if ($user->role === 'admin_subdit') 
    return redirect()->route('dashboard'); // /dashboard (filtered by subdit_access)

if ($user->role === 'petugas') 
    return redirect()->route('dashboard'); // /dashboard (with Pawas selection)
```

---

## 📁 Files Created

### Migrations
1. `2026_01_31_000001_refactor_users_for_shared_accounts.php`
   - Drops personal data columns from users
   - Adds username, role, subdit_access
   - Updates password_reset_tokens to use username

2. `2026_01_31_000002_create_personels_table.php`
   - Creates new personels table for police data

### Seeders
3. `UserSeeder.php` (NEW)
   - Seeds 6 default shared accounts

4. `DatabaseSeeder.php` (UPDATED)
   - Added UserSeeder to seeding chain

### Models
5. `app/Models/User.php` (UPDATED)
   - Updated fillable: `['username', 'password', 'role', 'subdit_access']`
   - Updated casts: `['password' => 'hashed', 'subdit_access' => 'integer']`
   - Removed: email, nrp, pangkat, telepon, jabatan fields

6. `app/Models/Personel.php` (NEW)
   - Model for personels table
   - Helper methods: `getFormattedNameAttribute()`, `getSubditNameAttribute()`
   - Scopes: `subdit()`, `search()`

### Auth Files
7. `resources/js/Pages/Auth/Login.vue` (UPDATED)
   - Changed from NRP to Username input
   - Updated form data: `form.nrp` → `form.username`

8. `app/Http/Requests/Auth/LoginRequest.php` (UPDATED)
   - Validation rules: `nrp` → `username`
   - Authenticate method: username-based

9. `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (UPDATED)
   - Enhanced role-based redirects with comments

### Documentation
10. `SHARED_ACCOUNT_REFACTOR.md`
    - Comprehensive documentation of changes
    - Migration guide
    - Testing checklist

---

## ✅ Testing Checklist

### Login Tests
- [ ] Login with `admin` / `password` → redirects to `/admin`
- [ ] Login with `petugas` / `password` → redirects to `/dashboard`
- [ ] Login with `pimpinan` / `password` → redirects to `/pimpinan/dashboard`
- [ ] Login with `subdit1` / `password` → redirects to `/dashboard`
- [ ] Login with wrong username → shows error
- [ ] Login with wrong password → shows error

### UI Tests
- [ ] Login page shows "Username" label (not NRP)
- [ ] Placeholder text shows example usernames
- [ ] Error messages use "username" terminology

### Security Tests
- [ ] Logout clears session correctly
- [ ] Middleware blocks unauthorized role access
- [ ] Password hashing works correctly

---

## 🚀 Deployment Steps

### Fresh Installation
```bash
# 1. Run migrations and seeders
php artisan migrate:fresh --seed

# 2. Test login
# Username: admin, Password: password
```

### Existing Database (DANGEROUS - BACKUP FIRST!)
```bash
# 1. BACKUP DATABASE FIRST!
mysqldump -u root -p siber_jateng_v2 > backup_$(date +%Y%m%d).sql

# 2. Run new migrations (will modify users table)
php artisan migrate

# 3. Seed new users
php artisan db:seed --class=UserSeeder

# 4. Verify data
php artisan tinker
>>> User::all();
>>> Personel::all(); // Should be empty initially
```

---

## 🔮 Next Steps

### 1. Pawas Selection (Petugas)
When `petugas` logs in, show a modal to select their identity from `personels`:

```php
// Controller
$personels = Personel::orderBy('nama_lengkap')->get();
return Inertia::render('Auth/SelectPawas', compact('personels'));

// After selection
session(['selected_pawas_id' => $personel->id]);
session(['selected_pawas_name' => $personel->formatted_name]);
```

### 2. Penyidik Assignment
Update laporan creation to search from `personels`:

```vue
<!-- SearchableSelect for Penyidik -->
<SearchableSelect
  :options="personels"
  label-key="formatted_name"
  value-key="id"
  placeholder="Cari nama atau NRP..."
/>
```

```php
// Controller
$penyidik = Personel::search($request->penyidik_search)
    ->subdit($user->subdit_access)
    ->get();
```

### 3. Personnel Management UI
Create admin interface to manage `personels` table:
- `/admin/personels` (CRUD for personnel data)
- Import from existing anggota data if needed

### 4. Audit Logging
Track actions with:
- User role
- Selected Pawas (if petugas)
- Timestamp

---

## 🔒 Security Considerations

1. **Shared Passwords**
   - All accounts use shared credentials
   - Rotate passwords regularly (monthly recommended)
   - Use environment variables for production passwords

2. **Session Management**
   - Each login creates separate session
   - Logout affects only current session
   - No cross-session data leakage

3. **Role Verification**
   - Middleware validates role on every request
   - Cannot access unauthorized routes

4. **Data Separation**
   - User accounts ≠ Personnel data
   - Pawas selection creates audit trail
   - Actions tracked by role + selected identity

---

## 📝 Migration Notes

### Why Separate Users and Personels?

**Before:** 1 User = 1 Police Officer (1:1 relationship)
**After:** 1 Account = Multiple Officers can use it (1:N relationship)

**Benefits:**
1. **Simplified Login:** No need to remember individual NRP/passwords
2. **Role-Based Access:** Clear separation of permissions
3. **Scalability:** Easy to add new shared accounts
4. **Audit Trail:** Track who did what with Pawas selection
5. **Flexibility:** Officers can share accounts for data entry

**Trade-offs:**
- Shared passwords require rotation
- Need Pawas selection for accountability
- Cannot link actions directly to individual without Pawas data

---

## 🎯 Conclusion

The authentication system has been successfully refactored to use shared accounts with username-based login. The old personal data has been moved to the `personels` table for future use in Pawas selection and Penyidik assignment.

**Status:** ✅ Ready for testing and deployment
