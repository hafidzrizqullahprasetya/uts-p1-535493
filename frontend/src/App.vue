<template>
  <div class="app-container">
    <!-- Halaman Login (Jika belum login) -->
    <div v-if="!isAuthenticated" class="auth-wrapper">
      <div class="auth-card">
        <div class="auth-header">
          <div class="badge-tag">UTS KEPL 2026 — 535493</div>
          <h2>Masuk ke Sistem Aset Lab</h2>
          <p class="subtitle">Kelola inventaris laboratorium TRPL SV UGM</p>
        </div>

        <div v-if="loginError" class="alert alert-error">
          {{ loginError }}
        </div>

        <form @submit.prevent="handleLogin" class="auth-form">
          <div class="form-group">
            <label for="email">Alamat Email</label>
            <input
              id="email"
              v-model="loginForm.email"
              type="email"
              placeholder="admin@ugm.ac.id"
              required
            />
          </div>

          <div class="form-group">
            <label for="password">Kata Sandi</label>
            <input
              id="password"
              v-model="loginForm.password"
              type="password"
              placeholder="••••••••"
              required
            />
          </div>

          <button type="submit" class="btn btn-primary btn-block" :disabled="isSubmitting">
            <span v-if="isSubmitting">Memproses...</span>
            <span v-else>Masuk Sekarang</span>
          </button>
        </form>

        <div class="auth-footer">
          <p class="env-info">API URL: <code>{{ apiBaseUrl }}</code></p>
        </div>
      </div>
    </div>

    <!-- Halaman Dashboard & CRUD Aset (Jika terotentikasi) -->
    <div v-else class="dashboard-wrapper">
      <!-- Top Navbar -->
      <header class="navbar">
        <div class="nav-brand">
          <div class="brand-icon">🏛️</div>
          <div>
            <h1>Sistem Aset Lab TRPL</h1>
            <span class="subtext">UTS KEPL — NIM: 535493</span>
          </div>
        </div>

        <div class="nav-user">
          <div class="user-info">
            <span class="user-name">{{ currentUser?.name || 'Administrator' }}</span>
            <span class="user-email">{{ currentUser?.email }}</span>
          </div>
          <button @click="handleLogout" class="btn btn-outline-danger">
            Keluar
          </button>
        </div>
      </header>

      <!-- Main Content -->
      <main class="main-content">
        <!-- Notification Banner -->
        <div v-if="notification.message" :class="['alert', `alert-${notification.type}`]">
          {{ notification.message }}
        </div>

        <!-- Metric Cards -->
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-label">Total Aset</div>
            <div class="metric-value">{{ assets.length }}</div>
            <div class="metric-sub">Unit terdata di sistem</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Aset Tersedia</div>
            <div class="metric-value text-success">
              {{ assets.filter(a => a.status === 'tersedia').length }}
            </div>
            <div class="metric-sub">Siap digunakan di lab</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Sedang Dipinjam</div>
            <div class="metric-value text-warning">
              {{ assets.filter(a => a.status === 'dipinjam').length }}
            </div>
            <div class="metric-sub">Dalam masa peminjaman</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Total Nilai Aset</div>
            <div class="metric-value text-primary">
              {{ formatRupiah(totalAssetValue) }}
            </div>
            <div class="metric-sub">Estimasi nilai perolehan</div>
          </div>
        </div>

        <!-- Table Toolbar -->
        <div class="table-card">
          <div class="card-header">
            <div class="header-left">
              <h2>Daftar Inventaris Aset</h2>
              <p class="subtitle">API Endpoint: <code>{{ apiBaseUrl }}/assets</code></p>
            </div>
            <div class="header-right">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari nama, kode, kategori..."
                class="search-input"
              />
              <select v-model="statusFilter" class="filter-select">
                <option value="">Semua Status</option>
                <option value="tersedia">Tersedia</option>
                <option value="dipinjam">Dipinjam</option>
                <option value="rusak">Rusak</option>
                <option value="perawatan">Perawatan</option>
              </select>
              <button @click="openCreateModal" class="btn btn-primary">
                + Tambah Aset
              </button>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p>Memuat data aset dari server...</p>
          </div>

          <!-- Table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kode Aset</th>
                  <th>Nama Barang</th>
                  <th>Kategori</th>
                  <th>Lokasi</th>
                  <th>Nilai Perolehan</th>
                  <th>Status</th>
                  <th class="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredAssets.length === 0">
                  <td colspan="7" class="empty-state">
                    Tidak ada data aset yang cocok.
                  </td>
                </tr>
                <tr v-for="asset in filteredAssets" :key="asset.id">
                  <td>
                    <span class="code-badge">{{ asset.code }}</span>
                  </td>
                  <td class="font-medium">{{ asset.name }}</td>
                  <td>{{ asset.category }}</td>
                  <td>{{ asset.location || '-' }}</td>
                  <td>{{ formatRupiah(asset.price) }}</td>
                  <td>
                    <span :class="['badge', `badge-${asset.status}`]">
                      {{ asset.status }}
                    </span>
                  </td>
                  <td class="text-right actions-cell">
                    <button @click="openEditModal(asset)" class="btn-icon btn-edit" title="Ubah Aset">
                      ✏️ Edit
                    </button>
                    <button @click="handleDelete(asset)" class="btn-icon btn-delete" title="Hapus Aset">
                      🗑️ Hapus
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>

      <!-- Modal Tambah / Edit Aset -->
      <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-card">
          <div class="modal-header">
            <h3>{{ isEditMode ? 'Ubah Data Aset' : 'Tambah Aset Baru' }}</h3>
            <button @click="closeModal" class="close-btn">&times;</button>
          </div>

          <form @submit.prevent="saveAsset" class="modal-body">
            <div v-if="formErrors.general" class="alert alert-error">
              {{ formErrors.general }}
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label>Nama Aset / Barang *</label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Contoh: Laptop Dell Latitude"
                  required
                />
                <span v-if="formErrors.name" class="field-error">{{ formErrors.name[0] }}</span>
              </div>
              <div class="form-group flex-1">
                <label>Kode Aset (Unik) *</label>
                <input
                  v-model="form.code"
                  type="text"
                  placeholder="Contoh: AST-LAB-011"
                  required
                />
                <span v-if="formErrors.code" class="field-error">{{ formErrors.code[0] }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label>Kategori *</label>
                <input
                  v-model="form.category"
                  type="text"
                  placeholder="Contoh: Komputer, Jaringan"
                  required
                />
                <span v-if="formErrors.category" class="field-error">{{ formErrors.category[0] }}</span>
              </div>
              <div class="form-group flex-1">
                <label>Status Operasional *</label>
                <select v-model="form.status" required>
                  <option value="tersedia">Tersedia</option>
                  <option value="dipinjam">Dipinjam</option>
                  <option value="rusak">Rusak</option>
                  <option value="perawatan">Perawatan</option>
                </select>
                <span v-if="formErrors.status" class="field-error">{{ formErrors.status[0] }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label>Nilai Perolehan (Rp) *</label>
                <input
                  v-model.number="form.price"
                  type="number"
                  min="0"
                  step="1000"
                  placeholder="0"
                  required
                />
                <span v-if="formErrors.price" class="field-error">{{ formErrors.price[0] }}</span>
              </div>
              <div class="form-group flex-1">
                <label>Lokasi Penyimpanan</label>
                <input
                  v-model="form.location"
                  type="text"
                  placeholder="Contoh: HU.108 Lab RPL 1"
                />
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn btn-secondary">
                Batal
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                <span v-if="isSubmitting">Menyimpan...</span>
                <span v-else>{{ isEditMode ? 'Simpan Perubahan' : 'Buat Aset (201)' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import apiClient, { apiBaseUrl } from './api';

// State Autentikasi
const isAuthenticated = ref(false);
const currentUser = ref(null);
const isSubmitting = ref(false);
const loginError = ref('');

const loginForm = reactive({
  email: 'admin@ugm.ac.id',
  password: 'password123',
});

// State Data Aset
const assets = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const statusFilter = ref('');

// State Notifikasi
const notification = reactive({
  message: '',
  type: 'success',
});

const showNotification = (message, type = 'success') => {
  notification.message = message;
  notification.type = type;
  setTimeout(() => {
    notification.message = '';
  }, 4000);
};

// State Modal CRUD
const showModal = ref(false);
const isEditMode = ref(false);
const editingAssetId = ref(null);
const formErrors = ref({});

const form = reactive({
  name: '',
  code: '',
  category: '',
  status: 'tersedia',
  price: 0,
  location: '',
});

// Reset Form
const resetForm = () => {
  form.name = '';
  form.code = '';
  form.category = '';
  form.status = 'tersedia';
  form.price = 0;
  form.location = '';
  formErrors.value = {};
  editingAssetId.value = null;
};

// Hitung Total Nilai Aset
const totalAssetValue = computed(() => {
  return assets.value.reduce((acc, item) => acc + (Number(item.price) || 0), 0);
});

// Filter Aset berdasarkan search query & status
const filteredAssets = computed(() => {
  return assets.value.filter((item) => {
    const matchesSearch =
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.category.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesStatus = statusFilter.value ? item.status === statusFilter.value : true;
    return matchesSearch && matchesStatus;
  });
});

// Format Rupiah
const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

// Autentikasi: Login
const handleLogin = async () => {
  isSubmitting.value = true;
  loginError.value = '';
  try {
    const res = await apiClient.post('/login', loginForm);
    const token = res.data.token;
    const user = res.data.user;

    localStorage.setItem('auth_token', token);
    localStorage.setItem('auth_user', JSON.stringify(user));

    currentUser.value = user;
    isAuthenticated.value = true;
    showNotification('Login berhasil! Token Sanctum tersimpan.', 'success');
    await fetchAssets();
  } catch (err) {
    if (err.response && err.response.status === 401) {
      loginError.value = 'Email atau kata sandi tidak valid.';
    } else if (err.response && err.response.status === 422) {
      loginError.value = 'Mohon isi format email dan kata sandi dengan benar.';
    } else {
      loginError.value = 'Gagal terhubung ke backend server API.';
    }
  } finally {
    isSubmitting.value = false;
  }
};

// Autentikasi: Logout
const handleLogout = async () => {
  try {
    await apiClient.post('/logout');
  } catch (err) {
    console.error('Logout error:', err);
  } finally {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    isAuthenticated.value = false;
    currentUser.value = null;
    assets.value = [];
  }
};

// CRUD: Tampil Semua Data (GET /api/assets)
const fetchAssets = async () => {
  isLoading.value = true;
  try {
    const res = await apiClient.get('/assets');
    assets.value = res.data.data;
  } catch (err) {
    showNotification('Gagal mengambil data aset dari server.', 'error');
  } finally {
    isLoading.value = false;
  }
};

// Buka Modal Tambah
const openCreateModal = () => {
  resetForm();
  isEditMode.value = false;
  showModal.value = true;
};

// Buka Modal Edit
const openEditModal = (asset) => {
  resetForm();
  isEditMode.value = true;
  editingAssetId.value = asset.id;
  form.name = asset.name;
  form.code = asset.code;
  form.category = asset.category;
  form.status = asset.status;
  form.price = asset.price;
  form.location = asset.location || '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

// CRUD: Simpan Data (POST 201 Created atau PUT 200 OK)
const saveAsset = async () => {
  isSubmitting.value = true;
  formErrors.value = {};
  try {
    if (isEditMode.value) {
      const res = await apiClient.put(`/assets/${editingAssetId.value}`, form);
      const updatedAsset = res.data.data;
      const index = assets.value.findIndex((a) => a.id === editingAssetId.value);
      if (index !== -1) {
        assets.value[index] = updatedAsset;
      }
      showNotification('Aset berhasil diperbarui (Status HTTP: 200 OK)', 'success');
    } else {
      const res = await apiClient.post('/assets', form);
      assets.value.unshift(res.data.data);
      showNotification('Aset berhasil dibuat (Status HTTP: 201 Created)', 'success');
    }
    closeModal();
  } catch (err) {
    if (err.response && err.response.status === 422) {
      formErrors.value = err.response.data.errors || {};
      showNotification('Validasi gagal (Status HTTP: 422 Unprocessable Content)', 'error');
    } else {
      formErrors.value = { general: 'Terjadi kesalahan sistem saat menyimpan aset.' };
    }
  } finally {
    isSubmitting.value = false;
  }
};

// CRUD: Hapus Data (DELETE 200 OK)
const handleDelete = async (asset) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus aset "${asset.name}" (${asset.code})?`)) {
    return;
  }
  try {
    await apiClient.delete(`/assets/${asset.id}`);
    assets.value = assets.value.filter((a) => a.id !== asset.id);
    showNotification(`Aset "${asset.name}" berhasil dihapus.`, 'success');
  } catch (err) {
    showNotification('Gagal menghapus aset dari server.', 'error');
  }
};

// Inisialisasi saat komponen dimuat
onMounted(() => {
  const token = localStorage.getItem('auth_token');
  const user = localStorage.getItem('auth_user');

  if (token && user) {
    try {
      currentUser.value = JSON.parse(user);
      isAuthenticated.value = true;
      fetchAssets();
    } catch (e) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
    }
  }

  window.addEventListener('auth:unauthorized', () => {
    isAuthenticated.value = false;
    currentUser.value = null;
    showNotification('Sesi berakhir atau token tidak valid (401 Unauthorized)', 'error');
  });
});
</script>

<style scoped>
.app-container {
  min-height: 100vh;
}

/* Auth Layout */
.auth-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

.auth-card {
  width: 100%;
  max-width: 420px;
  background: #ffffff;
  border-radius: 12px;
  padding: 32px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);
}

.auth-header {
  text-align: center;
  margin-bottom: 24px;
}

.badge-tag {
  display: inline-block;
  padding: 4px 10px;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  margin-bottom: 12px;
}

.auth-header h2 {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
}

.auth-header .subtitle {
  font-size: 13px;
  color: #64748b;
  margin-top: 4px;
}

.form-group {
  margin-bottom: 16px;
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.btn {
  padding: 10px 16px;
  font-size: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-primary {
  background: #2563eb;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-secondary {
  background: #e2e8f0;
  color: #334155;
}

.btn-secondary:hover {
  background: #cbd5e1;
}

.btn-block {
  width: 100%;
}

.btn-outline-danger {
  background: transparent;
  border: 1px solid #f87171;
  color: #dc2626;
  padding: 6px 12px;
  font-size: 13px;
}

.btn-outline-danger:hover {
  background: #fef2f2;
}

.auth-footer {
  margin-top: 24px;
  text-align: center;
  font-size: 12px;
  color: #64748b;
  border-top: 1px solid #f1f5f9;
  padding-top: 16px;
}

/* Dashboard Layout */
.navbar {
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 16px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-icon {
  font-size: 28px;
}

.nav-brand h1 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.nav-brand .subtext {
  font-size: 12px;
  color: #64748b;
}

.nav-user {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-info {
  display: flex;
  flex-direction: column;
  text-align: right;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}

.user-email {
  font-size: 12px;
  color: #64748b;
}

.main-content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 32px 24px;
}

/* Metrics */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.metric-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 20px;
}

.metric-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
}

.metric-value {
  font-size: 24px;
  font-weight: 700;
  margin: 6px 0;
  color: #0f172a;
}

.metric-sub {
  font-size: 12px;
  color: #94a3b8;
}

.text-success { color: #10b981; }
.text-warning { color: #f59e0b; }
.text-primary { color: #2563eb; }

/* Table Card */
.table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.card-header h2 {
  font-size: 18px;
  font-weight: 700;
}

.card-header .subtitle {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search-input {
  width: 240px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.data-table th {
  background: #f8fafc;
  padding: 12px 20px;
  text-align: left;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 14px 20px;
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
}

.code-badge {
  font-family: monospace;
  font-size: 12px;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  color: #0f172a;
  border: 1px solid #cbd5e1;
}

.actions-cell {
  white-space: nowrap;
}

.btn-icon {
  padding: 6px 10px;
  font-size: 12px;
  margin-left: 6px;
}

.btn-edit {
  background: #eff6ff;
  color: #2563eb;
}

.btn-edit:hover {
  background: #dbeafe;
}

.btn-delete {
  background: #fef2f2;
  color: #dc2626;
}

.btn-delete:hover {
  background: #fee2e2;
}

.empty-state {
  text-align: center;
  padding: 40px !important;
  color: #94a3b8;
}

/* Modals */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 50;
}

.modal-card {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.close-btn {
  background: transparent;
  font-size: 24px;
  color: #94a3b8;
}

.modal-body {
  padding: 24px;
}

.form-row {
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
}

.flex-1 {
  flex: 1;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  border-top: 1px solid #f1f5f9;
  padding-top: 16px;
}

.alert {
  padding: 12px 16px;
  border-radius: 6px;
  font-size: 13px;
  margin-bottom: 16px;
}

.alert-error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.alert-success {
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.field-error {
  color: #dc2626;
  font-size: 11px;
  margin-top: 4px;
}
</style>
