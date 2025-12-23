# UserSysLogHelper - Dokumentasi Penggunaan

## Overview
`UserSysLogHelper` adalah helper untuk mencatat aktivitas user ke table `user_sys_log`. Helper ini dapat dipanggil di semua controller untuk mencatat aktivitas user seperti login, create, update, delete, approve, dll.

## Struktur Table user_sys_log
```sql
- id (uuid) - Primary key
- user_id (int) - ID user yang melakukan aktivitas
- user_name (string) - Nama user yang melakukan aktivitas
- services (string) - Format: functionName.ControllerName (contoh: store.SphController)
- activity (string) - Deskripsi aktivitas (contoh: "Simpan data baru")
- timestamp (datetime) - Waktu aktivitas
```

## Cara Penggunaan

### 1. Import Helper di Controller
```php
use App\Helpers\UserSysLogHelper;
```

### 2. Method yang Tersedia

#### A. `logFromAuth($authResult, $controllerName, $functionName, $activity = null)`
**Digunakan ketika sudah ada AuthValidator::validateTokenAndClient**

```php
public function store(Request $request)
{
    $result = AuthValidator::validateTokenAndClient($request);
    if (!is_array($result) || !$result['status']) {
        return $result;
    }
    
    // ... kode lainnya ...
    
    // Log aktivitas
    UserSysLogHelper::logFromAuth($result, 'Sph', 'store');
    
    return response()->json(['message' => 'Data berhasil disimpan']);
}
```

#### B. `logFromToken($token, $controllerName, $functionName, $activity = null)`
**Digunakan ketika sudah ada token Sanctum**

```php
public function update(Request $request, $id)
{
    $token = $request->user()->currentAccessToken();
    
    // ... kode lainnya ...
    
    // Log aktivitas
    UserSysLogHelper::logFromToken($token, 'PurchaseOrder', 'update');
    
    return response()->json(['message' => 'Data berhasil diupdate']);
}
```

#### C. `log($userId, $userName, $controllerName, $functionName, $activity = null)`
**Digunakan ketika sudah punya data user manual**

```php
public function login(Request $request)
{
    // ... kode login ...
    
    $user = Auth::user();
    
    // Log aktivitas
    UserSysLogHelper::log(
        $user->id, 
        $user->first_name . ' ' . $user->last_name, 
        'Auth', 
        'login'
    );
    
    return response()->json(['message' => 'Login successful']);
}
```

### 3. Activity Auto-Generation
Helper akan otomatis generate activity description berdasarkan function name:

| Function Name | Activity Description |
|---------------|---------------------|
| `login` | "Login ke sistem" |
| `logout` | "Logout dari sistem" |
| `store` | "Simpan data baru" |
| `create` | "Buat data baru" |
| `update` | "Update data" |
| `destroy` | "Hapus data" |
| `delete` | "Hapus data" |
| `verify` | "Verifikasi/Approve data" |
| `approve` | "Approve data" |
| `reject` | "Reject data" |
| `upload` | "Upload file" |
| `generatePDF` | "Generate PDF" |
| `list` | "Lihat daftar data" |
| `show` | "Lihat detail data" |

### 4. Custom Activity Description
Anda bisa override activity description dengan parameter ke-4:

```php
UserSysLogHelper::logFromAuth($result, 'Sph', 'approveSph', 'Approve SPH: approve');
UserSysLogHelper::logFromAuth($result, 'Sph', 'approveSph', 'Reject SPH: reject');
```

## Contoh Implementasi di Controller

### SphController
```php
public function store(Request $request)
{
    $result = AuthValidator::validateTokenAndClient($request);
    if (!is_array($result) || !$result['status']) {
        return $result;
    }
    
    // ... kode simpan data ...
    
    DB::commit();
    
    // Log aktivitas user
    UserSysLogHelper::logFromAuth($result, 'Sph', 'store');
    
    return response()->json(['message' => 'SPH berhasil disimpan!'], 201);
}

public function list(Request $request)
{
    $result = AuthValidator::validateTokenAndClient($request);
    if (!is_array($result) || !$result['status']) {
        return $result;
    }
    
    // ... kode ambil data ...
    
    // Log aktivitas user
    UserSysLogHelper::logFromAuth($result, 'Sph', 'list');
    
    return response()->json(['data' => $data, 'cards' => $cards]);
}

public function approveSph(Request $request, $id)
{
    $result = AuthValidator::validateTokenAndClient($request);
    if (!is_array($result) || !$result['status']) {
        return $result;
    }
    
    // ... kode approval ...
    
    DB::commit();
    
    // Log aktivitas user dengan custom activity
    UserSysLogHelper::logFromAuth($result, 'Sph', 'approveSph', 'Approve SPH: ' . $status);
    
    return response()->json(['message' => 'Konfirmasi berhasil disimpan']);
}
```

### AuthController
```php
public function login(Request $request)
{
    // ... kode login ...
    
    $user = Auth::user();
    
    // Log aktivitas login
    UserSysLogHelper::log($user->id, $user->first_name . ' ' . $user->last_name, 'Auth', 'login');
    
    return response()->json(['message' => 'Login successful']);
}

public function updateProfile(Request $request)
{
    $result = AuthValidator::validateTokenAndClient($request);
    if (!is_array($result) || !$result['status']) {
        return $result;
    }
    
    // ... kode update profile ...
    
    // Log aktivitas user
    UserSysLogHelper::logFromAuth($result, 'Auth', 'updateProfile');
    
    return response()->json(['message' => 'Profile updated successfully']);
}
```

### PurchaseOrderController
```php
public function store(Request $request)
{
    $result = AuthValidator::validateTokenAndClient($request);
    if (!is_array($result) || !$result['status']) {
        return $result;
    }
    
    // ... kode simpan PO ...
    
    // Log aktivitas user
    UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'store');
    
    return response()->json(['message' => 'PO berhasil dibuat']);
}

public function verify(Request $request, $poId)
{
    $result = AuthValidator::validateTokenAndClient($request);
    if (!is_array($result) || !$result['status']) {
        return $result;
    }
    
    // ... kode verify PO ...
    
    // Log aktivitas user
    UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'verify');
    
    return response()->json(['message' => 'PO berhasil diverifikasi']);
}
```

## Error Handling
Helper akan menangani error secara otomatis dan mencatat ke Laravel log jika terjadi masalah:

```php
try {
    // Insert ke table user_sys_log
    DB::table('user_sys_log')->insert([...]);
    return true;
} catch (\Exception $e) {
    // Log error jika terjadi masalah
    Log::error('UserSysLog Error: ' . $e->getMessage());
    return false;
}
```

## Tips Penggunaan

1. **Panggil setelah operasi berhasil** - Log aktivitas setelah DB::commit() atau setelah operasi berhasil
2. **Gunakan try-catch** - Helper sudah handle error, tapi pastikan tidak mengganggu flow utama
3. **Konsisten naming** - Gunakan nama controller tanpa suffix 'Controller' (contoh: 'Sph', bukan 'SphController')
4. **Custom activity untuk detail** - Gunakan custom activity untuk memberikan informasi lebih detail

## Daftar Controller yang Perlu Diimplementasi

- [x] SphController
- [x] AuthController  
- [ ] PurchaseOrderController
- [ ] FinanceInvoiceController
- [ ] GoodReceiptController
- [ ] DeliveryRequestController
- [ ] DeliveryNoteController
- [ ] SupplierTransporterController
- [ ] CustomerDatabaseController
- [ ] MasterLovController
- [ ] ApprovalController
- [ ] FileUploadController
- [ ] SystemLogController
