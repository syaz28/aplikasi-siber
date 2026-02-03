# Authentication & Database Refactor - Shared Account Model

## Overview
Refactored from individual NRP-based authentication to a **Shared Account** model with username-based login. This separation allows multiple users to share role-based accounts while maintaining personnel data separately.

---

## Database Architecture

### 1. `users` Table (Authentication Only)
**Purpose:** Gatekeeper for login and role-based access control.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `username` | VARCHAR | Unique username (admin, petugas, subdit1, etc.) |
| `password` | VARCHAR | Hashed password |
| `role` | ENUM | admin, petugas, admin_subdit, pimpinan |
| `subdit_access` | INT NULL | For admin_subdit: 1=Ekonomi, 2=Sosial, 3=Khusus |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Key Changes:**
- ✅ Added: `username`, `role`, `subdit_access`
- ❌ Removed: `name`, `email`, `nrp`, `pangkat`, `telepon`, `jabatan`, `email_verified_at`

---

### 2. `personels` Table (Personnel Data)
**Purpose:** Store actual police officer data for Pawas selection, Penyidik assignment, and reporting.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `nrp` | VARCHAR(20) | Nomor Registrasi Pokok (unique) |
| `nama_lengkap` | VARCHAR | Full name |
| `pangkat` | VARCHAR NULL | Rank (AKBP, Kompol, etc.) |
| `jabatan` | VARCHAR NULL | Position/function |
| `subdit_id` | INT NULL | Subdit assignment |
| `unit_id` | VARCHAR NULL | Unit/work unit |
| `telepon` | VARCHAR(20) NULL | Phone number |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Usage:**
- Pawas selection dropdown
- Penyidik assignment
- Personnel reports
- Team management

---

## Seeded Accounts

### Default Credentials
All passwords: `password`

| Username | Role | Subdit Access | Redirect After Login |
|----------|------|---------------|----------------------|
| `admin` | admin | - | `/admin` (User Management) |
| `petugas` | petugas | - | `/dashboard` (with Pawas selection) |
| `pimpinan` | pimpinan | - | `/pimpinan/dashboard` (Executive Dashboard) |
| `subdit1` | admin_subdit | 1 (Ekonomi) | `/dashboard` (filtered cases) |
| `subdit2` | admin_subdit | 2 (Sosial) | `/dashboard` (filtered cases) |
| `subdit3` | admin_subdit | 3 (Khusus) | `/dashboard` (filtered cases) |

---

## Login Flow

### Frontend (`Login.vue`)
```vue
<input 
  type="text" 
  v-model="form.username" 
  placeholder="admin / petugas / pimpinan / subdit1"
/>
```

### Backend (`LoginRequest.php`)
```php
Auth::attempt([
    'username' => $request->username,
    'password' => $request->password
], $request->remember);
```

### Role-Based Redirects (`AuthenticatedSessionController.php`)
```php
if ($user->role === 'admin') 
    return redirect()->route('admin.dashboard');

if ($user->role === 'pimpinan') 
    return redirect()->route('pimpinan.dashboard');

if ($user->role === 'admin_subdit') 
    return redirect()->route('dashboard'); // filtered by subdit_access

if ($user->role === 'petugas') 
    return redirect()->route('dashboard'); // with Pawas selection
```

---

## Migration Guide

### Fresh Installation
```bash
php artisan migrate:fresh --seed
```

### Existing Database
```bash
# 1. Backup database first!
# 2. Run new migrations
php artisan migrate

# 3. Seed users
php artisan db:seed --class=UserSeeder
```

---

## Model Updates

### `User` Model
```php
protected $fillable = [
    'username',
    'password',
    'role',
    'subdit_access',
];
```

### `Personel` Model (New)
```php
protected $fillable = [
    'nrp',
    'nama_lengkap',
    'pangkat',
    'jabatan',
    'subdit_id',
    'unit_id',
    'telepon',
];
```

---

## Future Implementation

### Pawas Selection (for Petugas)
When `petugas` logs in, show modal to select their identity from `personels`:
```php
// Controller
$personels = Personel::orderBy('nama_lengkap')->get();

// Session
session(['selected_pawas_id' => $personel->id]);
```

### Penyidik Assignment
When creating a case, search from `personels` table:
```php
$penyidik = Personel::search($request->penyidik_search)
    ->subdit($user->subdit_access)
    ->get();
```

---

## Security Notes

1. **Shared Passwords:** All accounts use shared credentials. Rotate passwords regularly.
2. **Audit Logging:** Track actions with user role + selected Pawas (if applicable).
3. **Session Management:** Each login is tracked independently.
4. **Role Verification:** Middleware validates role on every request.

---

## Testing Checklist

- [ ] Login with `admin` -> redirects to `/admin`
- [ ] Login with `petugas` -> redirects to `/dashboard`
- [ ] Login with `pimpinan` -> redirects to `/pimpinan/dashboard`
- [ ] Login with `subdit1` -> redirects to `/dashboard` (Ekonomi filter)
- [ ] Login with wrong username -> error message
- [ ] Login with wrong password -> error message
- [ ] Logout -> redirects to login page
- [ ] Middleware blocks unauthorized roles

---

## Files Modified

### Migrations
- `2026_01_31_000001_refactor_users_for_shared_accounts.php`
- `2026_01_31_000002_create_personels_table.php`

### Seeders
- `UserSeeder.php` (new)
- `DatabaseSeeder.php` (updated)

### Models
- `app/Models/User.php` (updated)
- `app/Models/Personel.php` (new)

### Auth
- `resources/js/Pages/Auth/Login.vue` (updated)
- `app/Http/Requests/Auth/LoginRequest.php` (updated)
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (updated)

---

## Next Steps

1. Implement Pawas selection modal for `petugas` role
2. Update case creation to use `personels` for Penyidik selection
3. Add personnel management UI for admin
4. Implement audit logging with selected Pawas tracking
