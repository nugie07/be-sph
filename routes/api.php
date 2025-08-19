<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SphController;
use App\Http\Controllers\MasterLovController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\GoodReceiptController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\DeliveryRequestController;
use App\Http\Controllers\DataTransporterController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\FinanceInvoiceController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\SupplierTransporterController;
use App\Http\Controllers\CustomerDatabaseController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\MasterWilayahController;


// Logging
Route::post('/system-logs', [SystemLogController::class, 'store']);
Route::get('/user-sys-logs', [SystemLogController::class, 'userSysLog']);

// User Management Routes
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::get('/check-session', [AuthController::class, 'checkSession']);
Route::get('/delivery-tracking', [AuthController::class, 'getDeliveryTracking']);
Route::get('/profile-details', [AuthController::class, 'profileDetails']);
Route::post('/update-profile', [AuthController::class, 'updateProfile']);

// OTP dan Reset Password
Route::post('/auth/generate-otp', [AuthController::class, 'generateOTP']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOTP']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// SPH Api
Route::get('/sph/list', [SphController::class, 'list']);
Route::get('/get-customers', [SphController::class, 'getCustomers']);
Route::get('/get-products', [SphController::class, 'getProducts']);
Route::get('/get-customer-detail', [SphController::class, 'getCustomerDetail']);
Route::post('/sph/store', [SphController::class, 'store']);
Route::get('sph/{id}/remarks', [SphController::class, 'remarks']);
Route::delete('/sph/{id}', [SphController::class, 'destroy']);
Route::post('/sph/{id}/approval', [SphController::class, 'approveSph']);
Route::post('/send-sph-mail', [SphController::class, 'send']);
Route::post('/sph/{id}/generate-pdf', [SphController::class, 'generatePdf']);

// File Upload
Route::post('/upload', [FileUploadController::class, 'upload']);
// Good Receipt
Route::get('/good-receipts/list', [GoodReceiptController::class, 'list']);
Route::get('/good-receipts/{po_id}/detail', [GoodReceiptController::class, 'detail']);
Route::post('good-receipts/{po_id}/update', [GoodReceiptController::class, 'update']);
Route::get('/good-receipts/pdf/{path}', [GoodReceiptController::class, 'viewPdf'])->where('path', '.*');
Route::post('/good-receipts/{id}/revisi', [GoodReceiptController::class, 'revisi']);
Route::post('/tambah-good-receipts', [GoodReceiptController::class, 'tambahGr']);
Route::post('/good-receipts/{id}/cancel', [GoodReceiptController::class, 'cancelPo']);

// DRS
Route::get('/delivery-request', [DeliveryRequestController::class, 'index']);
Route::get('/delivery-request/po-list', [DeliveryRequestController::class, 'listPo']);
Route::get('/wilayah-list', [DeliveryRequestController::class, 'wilayahList']);
Route::post('/delivery-request/save', [DeliveryRequestController::class, 'store']);
Route::delete('/{id}', [DeliveryRequestController::class, 'destroy']);
Route::get('delivery-note-seq', [DeliveryRequestController::class, 'deliveryNoteSequence']);
Route::post('/delivery-request/{id}/generate-pdf', [DeliveryRequestController::class, 'generatePdf']);

// Master Transporter
Route::get('/transporter', [DataTransporterController::class, 'index']);
Route::post('/transporter', [DataTransporterController::class, 'store']);
Route::put('/transporter/{id}', [DataTransporterController::class, 'update']);
Route::delete('/transporter/{id}', [DataTransporterController::class, 'destroy']);

// Purchase Order Supplier and Transporter
Route::get('/purchase-order/list', [PurchaseOrderController::class, 'list']);
Route::get('/purchase-order/list-po-drs', [PurchaseOrderController::class, 'listPoDrs']);
Route::put('/purchase-order/{po_id}', [PurchaseOrderController::class, 'update']);
Route::post('/purchase-order/supplier', [PurchaseOrderController::class, 'savePoSupplier']);
Route::post('/purchase-order/po-transporter', [PurchaseOrderController::class, 'poTransporter']);
Route::post('purchase-order/verify/{id}', [PurchaseOrderController::class, 'verify']);
Route::post('/purchase-order/{id}/generate-pdf', [PurchaseOrderController::class, 'generatePDF']);
Route::get('/purchase-order/payment/list', [PurchaseOrderController::class, 'listPayment']);
Route::post('/purchase-order/payment/upload', [PurchaseOrderController::class, 'uploadPaymentReceipt']);


// Delivery Note API
// API route (if using web, prefix with /api)
Route::get('/delivery-note', [DeliveryNoteController::class, 'index']);
Route::get('/dn-source', [DeliveryNoteController::class, 'dnSource']);
Route::post('/delivery-note', [DeliveryNoteController::class, 'store']);
Route::get('/delivery-note/{id}', [DeliveryNoteController::class, 'show']);
Route::put('/delivery-note/{id}', [DeliveryNoteController::class, 'update']);
Route::delete('/delivery-note/{id}', [DeliveryNoteController::class, 'destroy']);
Route::get('/delivery-note-dn-source', [DeliveryNoteController::class, 'dnSource']);
Route::post('/delivery-note/upload-bast', [DeliveryNoteController::class, 'uploadBast']);

// Finance Invoice Api
Route::get('/finance/invoices', [FinanceInvoiceController::class, 'index']);
Route::get('/finance/unpaid-invoices', [FinanceInvoiceController::class, 'listUnpaidInvoices']);
Route::post('/finance/invoices', [FinanceInvoiceController::class, 'store']);
Route::get('/finance/invoices/{id}', [FinanceInvoiceController::class, 'show']);
Route::get('/finance/invoices/{id}/view-details', [FinanceInvoiceController::class, 'getViewDetails']);
Route::post('/finance/invoices/upload-receipt', [FinanceInvoiceController::class, 'uploadReceipt']);
Route::put('/finance/invoices/{id}', [FinanceInvoiceController::class, 'update']);
Route::post('/invoices', [FinanceInvoiceController::class, 'store'])->name('invoices.store');
Route::post('/finance/generate-invoice-no', [FinanceInvoiceController::class, 'generateInvoiceNo']);
Route::post('/finance/get-customer-by-po', [FinanceInvoiceController::class, 'getCustomerByPo']);


// Approval API
Route::get('/approval/list', [ApprovalController::class, 'list']);
Route::get('/approval/details', [ApprovalController::class, 'getApprovalDetails']);
Route::post('/approval/verify-invoice/{trx_id}', [ApprovalController::class, 'verifyInvoice']);

// Workflow Engine Management
Route::get('/approval/workflow-engine', [ApprovalController::class, 'listEngine']);
Route::put('/approval/workflow-engine/{id}', [ApprovalController::class, 'updateEngine']);
Route::get('/approval/roles-dropdown', [ApprovalController::class, 'getRolesForDropdown']);

// LOV Dropdown
Route::get('/master-lov/children', [MasterLovController::class, 'getChildren']);

// Master LOV - Lokasi Management
Route::get('/master-lov/lokasi/list', [MasterLovController::class, 'getListLokasi']);
Route::post('/master-lov/lokasi', [MasterLovController::class, 'createLokasi']);
Route::put('/master-lov/lokasi/{id}', [MasterLovController::class, 'updateLokasi']);
Route::delete('/master-lov/lokasi/{id}', [MasterLovController::class, 'deleteLokasi']);

// Remark API
Route::get('/remarks/{id}', [AuthController::class, 'remarks']);

// Supplier Transporter Management
Route::get('/supplier-transporter', [SupplierTransporterController::class, 'index']);
Route::get('/supplier-transporter/{id}', [SupplierTransporterController::class, 'show']);
Route::post('/supplier-transporter', [SupplierTransporterController::class, 'store']);
Route::put('/supplier-transporter/{id}', [SupplierTransporterController::class, 'update']);
Route::delete('/supplier-transporter/{id}', [SupplierTransporterController::class, 'destroy']);

// Customer Database Management
Route::get('/customer-database', [CustomerDatabaseController::class, 'index']);
Route::get('/customer-database/{id}', [CustomerDatabaseController::class, 'show']);
Route::post('/customer-database', [CustomerDatabaseController::class, 'store']);
Route::put('/customer-database/{id}', [CustomerDatabaseController::class, 'update']);
Route::delete('/customer-database/{id}', [CustomerDatabaseController::class, 'destroy']);
Route::get('/customer-database/list/customers', [CustomerDatabaseController::class, 'getCustomerList']);
Route::get('/customer-database/list/oat', [CustomerDatabaseController::class, 'getOatList']);
Route::delete('/customer-database/oat/{id}', [CustomerDatabaseController::class, 'deleteOat']);

// User Management
Route::get('/user-management', [UserManagementController::class, 'index']);
Route::get('/user-management/{id}', [UserManagementController::class, 'show']);
Route::post('/user-management', [UserManagementController::class, 'store']);
Route::put('/user-management/{id}', [UserManagementController::class, 'update']);
Route::delete('/user-management/{id}', [UserManagementController::class, 'destroy']);
Route::get('/user-management/list/roles', [UserManagementController::class, 'getRoles']);
Route::post('/user-management/roles', [UserManagementController::class, 'createRole']);
Route::get('/user-management/list/permissions', [UserManagementController::class, 'getPermissions']);
Route::get('/user-management/roles/{roleId}/permissions', [UserManagementController::class, 'getPermissionsByRole']);
Route::post('/user-management/roles/{roleId}/sync-permissions', [UserManagementController::class, 'syncPermissionsToRole']);
Route::get('/user-management/user/permissions', [UserManagementController::class, 'getUserPermissions']);
Route::get('/user-management/user/{userId}/permissions', [UserManagementController::class, 'getUserPermissions']);
Route::post('/user-management/{id}/reset-password', [UserManagementController::class, 'resetPassword']);

// Master Wilayah Management
Route::get('/master-wilayah', [MasterWilayahController::class, 'getList']);
Route::post('/master-wilayah', [MasterWilayahController::class, 'createWilayah']);
Route::put('/master-wilayah/{id}', [MasterWilayahController::class, 'updateWilayah']);
Route::delete('/master-wilayah/{id}', [MasterWilayahController::class, 'deleteWilayah']);
Route::get('/master-wilayah/request', [MasterWilayahController::class, 'wilayahRequest']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
