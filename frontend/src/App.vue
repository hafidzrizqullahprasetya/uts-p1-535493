<template>
  <div v-if="!isAuthenticated" class="login">
    <main class="login-sheet">
      <div class="brand">
        <span class="wordmark">Aset Lab TRPL</span>
        <span class="scope">DTEDI, Sekolah Vokasi UGM</span>
      </div>

      <h1>Masuk ke inventaris laboratorium</h1>
      <p class="lead">Data alat, ruang penyimpanan, dan status peminjaman di lab TRPL.</p>

      <p v-if="loginError" class="notice notice-error" role="alert">{{ loginError }}</p>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="field">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="loginForm.email"
            type="email"
            autocomplete="username"
            placeholder="admin@ugm.ac.id"
            required
          />
        </div>

        <div class="field">
          <label for="password">Kata sandi</label>
          <input
            id="password"
            v-model="loginForm.password"
            type="password"
            autocomplete="current-password"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="isSubmitting">
          {{ isSubmitting ? 'Memeriksa akun...' : 'Masuk' }}
        </button>
      </form>

      <p class="server">Server API <span class="mono">{{ apiBaseUrl }}</span></p>
    </main>

    <p class="login-foot">UTS KEPL 2026 · Hafidz Rizqullah Prasetya · NIM 535493</p>
  </div>

  <div v-else class="app">
    <header class="topbar">
      <div class="brand">
        <span class="wordmark">Aset Lab TRPL</span>
        <span class="scope">DTEDI, SV UGM</span>
      </div>

      <div class="account">
        <div class="account-id">
          <span class="account-name">{{ currentUser?.name || 'Administrator' }}</span>
          <span class="account-email">{{ currentUser?.email }}</span>
        </div>
        <button @click="handleLogout" class="btn btn-secondary">Keluar</button>
      </div>
    </header>

    <main class="sheet">
      <div class="title-row">
        <div>
          <h1>Inventaris aset</h1>
          <p class="finding">{{ findingText }}</p>
        </div>
        <button @click="openCreateModal" class="btn btn-primary">Tambah aset</button>
      </div>

      <p
        v-if="notification.message"
        :class="['notice', `notice-${notification.type}`]"
        role="status"
      >
        {{ notification.message }}
      </p>

      <section class="readiness" aria-labelledby="readiness-title">
        <h2 id="readiness-title" class="visually-hidden">Kesiapan alat</h2>

        <dl class="kpis">
          <div class="kpi kpi-main">
            <dt>Siap dipakai</dt>
            <dd class="num">
              {{ statusCount.tersedia }}<span class="unit"> dari {{ assets.length }} alat</span>
            </dd>
          </div>
          <div class="kpi">
            <dt>Sedang dipinjam</dt>
            <dd class="num">{{ statusCount.dipinjam }}</dd>
          </div>
          <div class="kpi">
            <dt>Perawatan atau rusak</dt>
            <dd class="num">{{ statusCount.perawatan + statusCount.rusak }}</dd>
          </div>
          <div class="kpi">
            <dt>Nilai perolehan</dt>
            <dd class="num">{{ formatRupiah(totalAssetValue) }}</dd>
          </div>
        </dl>

        <div v-if="assets.length" class="strip" role="img" :aria-label="stripLabel">
          <div
            v-for="asset in assetsByStatus"
            :key="asset.id"
            class="strip-cell"
            :title="`${asset.code} · ${asset.name} · ${statusLabel(asset.status)}`"
          >
            <span :class="['strip-bar', `bar-${asset.status}`]"></span>
            <span v-if="assets.length <= 16" class="strip-code mono">{{ asset.code }}</span>
          </div>
        </div>

        <ul v-if="assets.length" class="legend">
          <li v-for="s in statuses.filter((s) => statusCount[s.value])" :key="s.value">
            <span :class="['swatch', `bar-${s.value}`]"></span>
            {{ s.label }} <span class="num">{{ statusCount[s.value] }}</span>
          </li>
        </ul>
      </section>

      <section class="list" aria-labelledby="list-title">
        <h2 id="list-title" class="visually-hidden">Daftar aset</h2>

        <div class="tabs" role="tablist" aria-label="Saring menurut status">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            role="tab"
            :aria-selected="statusFilter === tab.value"
            :class="['tab', { active: statusFilter === tab.value }]"
            @click="statusFilter = tab.value"
          >
            {{ tab.label }} <span class="num tab-count">{{ tab.count }}</span>
          </button>
        </div>

        <div class="toolbar">
          <label for="search" class="visually-hidden">Cari aset</label>
          <input
            id="search"
            v-model="searchQuery"
            type="search"
            placeholder="Cari nama, kode, kategori, atau ruang"
            class="search"
          />
          <span class="count num">{{ filteredAssets.length }} aset</span>
        </div>

        <p v-if="isLoading" class="state">Memuat data aset dari server...</p>

        <div v-else-if="loadError" class="state state-error" role="alert">
          <p>Data aset tidak bisa dimuat dari server.</p>
          <button @click="fetchAssets" class="btn btn-secondary">Coba lagi</button>
        </div>

        <div v-else-if="assets.length === 0" class="state">
          <p>Belum ada alat yang tercatat.</p>
          <button @click="openCreateModal" class="btn btn-secondary">Tambah alat pertama</button>
        </div>

        <div v-else-if="filteredAssets.length === 0" class="state">
          <p>Tidak ada aset yang cocok dengan pencarian ini.</p>
          <button @click="resetFilters" class="btn btn-secondary">Tampilkan semua</button>
        </div>

        <table v-else class="asset-table">
          <thead>
            <tr>
              <th scope="col">Kode</th>
              <th scope="col">Nama barang</th>
              <th scope="col">Ruang</th>
              <th scope="col" class="right">Nilai perolehan</th>
              <th scope="col">Status</th>
              <th scope="col"><span class="visually-hidden">Aksi</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="asset in filteredAssets" :key="asset.id">
              <td class="cell-code mono">{{ asset.code }}</td>
              <td class="cell-name">
                <span class="name">{{ asset.name }}</span>
                <span class="category">{{ asset.category }}</span>
              </td>
              <td class="cell-room">{{ asset.location || '-' }}</td>
              <td class="cell-price right num">{{ formatRupiah(asset.price) }}</td>
              <td class="cell-status">
                <span :class="['pill', `pill-${asset.status}`]">{{ statusLabel(asset.status) }}</span>
              </td>
              <td class="cell-actions">
                <button @click="openEditModal(asset)" class="btn btn-text" :aria-label="`Ubah ${asset.code}`">
                  Ubah
                </button>
                <button @click="handleDelete(asset)" class="btn btn-text danger" :aria-label="`Hapus ${asset.code}`">
                  Hapus
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>

    <div v-if="showModal" class="backdrop" @click.self="closeModal">
      <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
        <div class="dialog-head">
          <h2 id="dialog-title">{{ isEditMode ? `Ubah ${form.code || 'aset'}` : 'Tambah aset' }}</h2>
          <button @click="closeModal" class="btn btn-text" aria-label="Tutup">Tutup</button>
        </div>

        <form @submit.prevent="saveAsset" class="dialog-body" novalidate>
          <p v-if="formErrors.general" class="notice notice-error" role="alert">{{ formErrors.general }}</p>

          <div class="form-grid">
            <div class="field">
              <label for="f-name">Nama barang</label>
              <input id="f-name" ref="firstField" v-model="form.name" type="text" placeholder="Laptop Dell Latitude 5430" required />
              <span v-if="formErrors.name" class="field-error">{{ formErrors.name[0] }}</span>
            </div>
            <div class="field">
              <label for="f-code">Kode inventaris</label>
              <input id="f-code" v-model="form.code" type="text" class="mono" placeholder="AST-LAB-011" required />
              <span v-if="formErrors.code" class="field-error">{{ formErrors.code[0] }}</span>
            </div>
            <div class="field">
              <label for="f-category">Kategori</label>
              <input id="f-category" v-model="form.category" type="text" placeholder="Perangkat Jaringan" required />
              <span v-if="formErrors.category" class="field-error">{{ formErrors.category[0] }}</span>
            </div>
            <div class="field">
              <label for="f-status">Status</label>
              <select id="f-status" v-model="form.status" required>
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
              <span v-if="formErrors.status" class="field-error">{{ formErrors.status[0] }}</span>
            </div>
            <div class="field">
              <label for="f-price">Nilai perolehan (Rp)</label>
              <input id="f-price" v-model.number="form.price" type="number" min="0" step="1000" class="num" required />
              <span v-if="formErrors.price" class="field-error">{{ formErrors.price[0] }}</span>
            </div>
            <div class="field">
              <label for="f-location">Ruang <span class="optional">(opsional)</span></label>
              <input id="f-location" v-model="form.location" type="text" placeholder="HU.108 Laboratorium RPL 1" />
              <span v-if="formErrors.location" class="field-error">{{ formErrors.location[0] }}</span>
            </div>
          </div>

          <div class="dialog-foot">
            <button type="button" @click="closeModal" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              {{ isSubmitting ? 'Menyimpan...' : isEditMode ? 'Simpan perubahan' : 'Simpan aset' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import apiClient, { apiBaseUrl } from './api';

const statuses = [
  { value: 'tersedia', label: 'Tersedia' },
  { value: 'dipinjam', label: 'Dipinjam' },
  { value: 'perawatan', label: 'Perawatan' },
  { value: 'rusak', label: 'Rusak' },
];

const statusLabel = (value) => statuses.find((s) => s.value === value)?.label || value;

const isAuthenticated = ref(false);
const currentUser = ref(null);
const isSubmitting = ref(false);
const loginError = ref('');

const loginForm = reactive({
  email: 'admin@ugm.ac.id',
  password: 'password123',
});

const assets = ref([]);
const isLoading = ref(false);
const loadError = ref(false);
const searchQuery = ref('');
const statusFilter = ref('');

const notification = reactive({
  message: '',
  type: 'success',
});

let notificationTimer = null;

const showNotification = (message, type = 'success') => {
  notification.message = message;
  notification.type = type;
  clearTimeout(notificationTimer);
  notificationTimer = setTimeout(() => {
    notification.message = '';
  }, 5000);
};

const showModal = ref(false);
const isEditMode = ref(false);
const editingAssetId = ref(null);
const formErrors = ref({});
const firstField = ref(null);

const form = reactive({
  name: '',
  code: '',
  category: '',
  status: 'tersedia',
  price: 0,
  location: '',
});

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

const statusCount = computed(() => {
  const count = { tersedia: 0, dipinjam: 0, perawatan: 0, rusak: 0 };
  assets.value.forEach((item) => {
    if (item.status in count) count[item.status] += 1;
  });
  return count;
});

const totalAssetValue = computed(() => {
  return assets.value.reduce((acc, item) => acc + (Number(item.price) || 0), 0);
});

const assetsByStatus = computed(() => {
  const order = statuses.map((s) => s.value);
  return [...assets.value].sort((a, b) => order.indexOf(a.status) - order.indexOf(b.status));
});

const findingText = computed(() => {
  const total = assets.value.length;
  if (isLoading.value && total === 0) return 'Memuat data aset...';
  if (total === 0) return 'Belum ada alat yang tercatat.';
  const away = total - statusCount.value.tersedia;
  if (away === 0) return `Semua ${total} alat ada di ruang dan siap dipakai.`;
  const parts = statuses
    .filter((s) => s.value !== 'tersedia' && statusCount.value[s.value])
    .map((s) => `${statusCount.value[s.value]} ${s.label.toLowerCase()}`);
  return `${away} dari ${total} alat belum bisa dipakai: ${parts.join(', ')}.`;
});

const stripLabel = computed(() => {
  return statuses
    .filter((s) => statusCount.value[s.value])
    .map((s) => `${statusCount.value[s.value]} ${s.label.toLowerCase()}`)
    .join(', ');
});

const tabs = computed(() => [
  { value: '', label: 'Semua', count: assets.value.length },
  ...statuses.map((s) => ({ ...s, count: statusCount.value[s.value] })),
]);

const filteredAssets = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  return assets.value.filter((item) => {
    const matchesSearch =
      !query ||
      [item.name, item.code, item.category, item.location]
        .filter(Boolean)
        .some((field) => field.toLowerCase().includes(query));
    const matchesStatus = statusFilter.value ? item.status === statusFilter.value : true;
    return matchesSearch && matchesStatus;
  });
});

const resetFilters = () => {
  searchQuery.value = '';
  statusFilter.value = '';
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

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
    await fetchAssets();
  } catch (err) {
    if (err.response && err.response.status === 401) {
      loginError.value = 'Email atau kata sandi salah.';
    } else if (err.response && err.response.status === 422) {
      loginError.value = 'Isi email dengan format yang benar dan kata sandi tidak boleh kosong.';
    } else {
      loginError.value = `Server API di ${apiBaseUrl} tidak bisa dihubungi.`;
    }
  } finally {
    isSubmitting.value = false;
  }
};

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

const fetchAssets = async () => {
  isLoading.value = true;
  loadError.value = false;
  try {
    const res = await apiClient.get('/assets');
    assets.value = res.data.data;
  } catch (err) {
    loadError.value = true;
  } finally {
    isLoading.value = false;
  }
};

const openModal = async () => {
  showModal.value = true;
  await nextTick();
  firstField.value?.focus();
};

const openCreateModal = () => {
  resetForm();
  isEditMode.value = false;
  openModal();
};

const openEditModal = (asset) => {
  resetForm();
  isEditMode.value = true;
  editingAssetId.value = asset.id;
  form.name = asset.name;
  form.code = asset.code;
  form.category = asset.category;
  form.status = asset.status;
  form.price = Number(asset.price);
  form.location = asset.location || '';
  openModal();
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

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
      showNotification(`Perubahan ${updatedAsset.code} tersimpan.`, 'success');
    } else {
      const res = await apiClient.post('/assets', form);
      assets.value.unshift(res.data.data);
      showNotification(`${res.data.data.code} ditambahkan ke inventaris.`, 'success');
    }
    closeModal();
  } catch (err) {
    if (err.response && err.response.status === 422) {
      formErrors.value = err.response.data.errors || {};
    } else {
      formErrors.value = { general: 'Aset belum tersimpan karena server tidak merespons. Coba lagi.' };
    }
  } finally {
    isSubmitting.value = false;
  }
};

const handleDelete = async (asset) => {
  if (!confirm(`Hapus ${asset.code} (${asset.name}) dari inventaris?`)) {
    return;
  }
  try {
    await apiClient.delete(`/assets/${asset.id}`);
    assets.value = assets.value.filter((a) => a.id !== asset.id);
    showNotification(`${asset.code} dihapus dari inventaris.`, 'success');
  } catch (err) {
    showNotification(`${asset.code} gagal dihapus. Coba lagi.`, 'error');
  }
};

const handleKeydown = (event) => {
  if (event.key === 'Escape' && showModal.value) {
    closeModal();
  }
};

const handleUnauthorized = () => {
  isAuthenticated.value = false;
  currentUser.value = null;
  loginError.value = 'Sesi berakhir. Silakan masuk lagi.';
};

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

  window.addEventListener('keydown', handleKeydown);
  window.addEventListener('auth:unauthorized', handleUnauthorized);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown);
  window.removeEventListener('auth:unauthorized', handleUnauthorized);
});
</script>

<style scoped>
.brand {
  display: flex;
  align-items: baseline;
  gap: 10px;
  min-width: 0;
}

.wordmark {
  font-weight: 600;
  font-size: 15px;
  color: var(--accent);
  white-space: nowrap;
}

.scope {
  font-size: 13px;
  color: var(--muted);
  white-space: nowrap;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  font-size: 13px;
  font-weight: 500;
}

.optional {
  font-weight: 400;
  color: var(--muted);
}

.field-error {
  font-size: var(--fs-small);
  color: var(--negative);
}

.btn-block {
  width: 100%;
}

.notice {
  padding: 10px 14px;
  border-radius: var(--radius-control);
  font-size: 13px;
}

.notice-success {
  background: var(--pill-positive-bg);
  color: var(--pill-positive-ink);
}

.notice-error {
  background: var(--pill-negative-bg);
  color: var(--pill-negative-ink);
}

/* Login */
.login {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 24px 16px;
}

.login-sheet {
  width: 100%;
  max-width: 400px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sheet);
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.login-sheet .brand {
  margin-bottom: 8px;
}

.login-sheet h1 {
  font-size: var(--fs-title);
  font-weight: 600;
  line-height: 1.25;
  letter-spacing: -0.01em;
}

.lead {
  color: var(--muted);
  margin-top: -8px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 8px;
}

.login-form .btn {
  margin-top: 4px;
  height: 40px;
}

.server {
  font-size: 13px;
  color: var(--muted);
  padding-top: 16px;
  border-top: 1px solid var(--rule);
  overflow-wrap: anywhere;
}

.server .mono {
  color: var(--text);
}

.login-foot {
  font-size: 13px;
  color: var(--muted);
  text-align: center;
}

/* Shell */
.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.topbar {
  height: 56px;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.account {
  display: flex;
  align-items: center;
  gap: 16px;
}

.account-id {
  display: flex;
  flex-direction: column;
  text-align: right;
  line-height: 1.3;
}

.account-name {
  font-weight: 500;
}

.account-email {
  font-size: var(--fs-small);
  color: var(--muted);
}

.sheet {
  flex: 1;
  margin: 0 var(--sheet-inset) var(--sheet-inset);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sheet);
  padding: 32px var(--pad) 40px;
  display: flex;
  flex-direction: column;
  gap: var(--gap);
}

.title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.title-row h1 {
  font-size: var(--fs-title);
  font-weight: 600;
  line-height: 1.25;
  letter-spacing: -0.01em;
}

.finding {
  margin-top: 4px;
  color: var(--muted);
}

/* Readiness */
.readiness {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.kpis {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1.4fr;
  border: 1px solid var(--border);
  border-radius: var(--radius-card);
}

.kpi {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.kpi + .kpi {
  border-left: 1px solid var(--border);
}

.kpi dt {
  font-size: 13px;
  font-weight: 500;
  color: var(--muted);
}

.kpi dd {
  font-size: 22px;
  line-height: 1.2;
}

.kpi-main dd {
  font-size: var(--fs-kpi);
}

.unit {
  font-size: var(--fs-body);
  color: var(--muted);
}

.strip {
  display: flex;
  gap: 4px;
}

.strip-cell {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.strip-bar {
  height: 10px;
  border-radius: 2px;
}

.strip-code {
  font-size: 11px;
  color: var(--muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.bar-tersedia { background: var(--chart-ready); }
.bar-dipinjam { background: var(--chart-loan); }
.bar-perawatan { background: var(--chart-service); }
.bar-rusak { background: var(--chart-broken); }

.legend {
  list-style: none;
  display: flex;
  flex-wrap: wrap;
  gap: 8px 20px;
  font-size: 13px;
  color: var(--muted);
}

.legend li {
  display: flex;
  align-items: center;
  gap: 6px;
}

.legend .num {
  color: var(--text);
}

.swatch {
  width: 10px;
  height: 10px;
  border-radius: 2px;
}

/* List */
.list {
  display: flex;
  flex-direction: column;
}

.tabs {
  display: flex;
  gap: 24px;
  border-bottom: 1px solid var(--border);
  overflow-x: auto;
  scrollbar-width: none;
}

.tab {
  height: 40px;
  padding: 0 2px;
  color: var(--muted);
  font-weight: 500;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  white-space: nowrap;
  transition: color 0.15s ease, border-color 0.15s ease;
}

.tab:hover {
  color: var(--text);
}

.tab.active {
  color: var(--text);
  border-bottom-color: var(--accent);
}

.tab-count {
  color: var(--muted);
  font-weight: 400;
  margin-left: 2px;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 0 8px;
}

.search {
  width: 320px;
  max-width: 100%;
}

.count {
  color: var(--muted);
  white-space: nowrap;
}

.state {
  padding: 48px 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 12px;
  color: var(--muted);
  border-top: 1px solid var(--rule);
}

.state-error p {
  color: var(--negative);
}

.asset-table {
  width: 100%;
  border-collapse: collapse;
}

.asset-table th {
  font-size: var(--fs-small);
  font-weight: 500;
  color: var(--muted);
  text-align: left;
  padding: 10px 12px;
  white-space: nowrap;
}

.asset-table td {
  padding: 10px 12px;
  height: 60px;
  border-top: 1px solid var(--rule);
  vertical-align: middle;
}

.asset-table th:first-child,
.asset-table td:first-child {
  padding-left: 0;
}

.asset-table th:last-child,
.asset-table td:last-child {
  padding-right: 0;
}

.asset-table tbody tr {
  transition: background-color 0.15s ease;
}

.asset-table tbody tr:hover {
  background: var(--raised);
}

.right {
  text-align: right !important;
}

.cell-code {
  font-size: 13px;
  white-space: nowrap;
}

.cell-name {
  min-width: 220px;
}

.cell-name .name {
  display: block;
  font-weight: 500;
}

.cell-name .category {
  display: block;
  font-size: var(--fs-small);
  color: var(--muted);
}

.cell-room {
  color: var(--muted);
}

.cell-price {
  white-space: nowrap;
}

.cell-actions {
  text-align: right;
  white-space: nowrap;
}

/* Dialog */
.backdrop {
  position: fixed;
  inset: 0;
  background: rgba(19, 20, 22, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 50;
}

.dialog {
  width: 100%;
  max-width: 600px;
  max-height: calc(100vh - 32px);
  overflow-y: auto;
  background: var(--surface);
  border-radius: 12px;
  box-shadow: 0 16px 40px rgba(19, 20, 22, 0.18);
}

.dialog-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 16px 0 24px;
}

.dialog-head h2 {
  font-size: 18px;
  font-weight: 600;
}

.dialog-body {
  padding: 16px 24px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.dialog-foot {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 8px;
}

@media (max-width: 900px) {
  .kpis {
    grid-template-columns: 1fr 1fr;
  }

  .kpi:nth-child(3) {
    border-left: none;
  }

  .kpi:nth-child(n + 3) {
    border-top: 1px solid var(--border);
  }

  .asset-table th:nth-child(3),
  .asset-table td:nth-child(3) {
    display: none;
  }
}

@media (max-width: 640px) {
  .topbar {
    padding: 0 16px;
  }

  .scope,
  .account-id {
    display: none;
  }

  .sheet {
    margin: 0;
    border-radius: 0;
    border-left: none;
    border-right: none;
    border-bottom: none;
    padding: 24px 16px 32px;
  }

  .title-row {
    flex-direction: column;
    align-items: stretch;
  }

  .title-row .btn {
    height: 44px;
  }

  .kpi {
    padding: 12px 14px;
  }

  .kpi dd {
    font-size: 18px;
  }

  .kpi-main dd {
    font-size: 24px;
  }

  .strip-code {
    display: none;
  }

  .toolbar {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .search {
    width: 100%;
    height: 44px;
  }

  .tab {
    height: 44px;
  }

  .asset-table thead {
    display: none;
  }

  .asset-table,
  .asset-table tbody {
    display: block;
  }

  .asset-table tr {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 4px 12px;
    padding: 14px 0;
    border-top: 1px solid var(--rule);
  }

  .asset-table td {
    display: block;
    height: auto;
    padding: 0 !important;
    border: none;
  }

  .asset-table td:nth-child(3) {
    display: none;
  }

  .cell-code {
    grid-column: 1;
    grid-row: 1;
    font-size: var(--fs-small);
    color: var(--muted);
  }

  .cell-status {
    grid-column: 2;
    grid-row: 1;
    text-align: right;
  }

  .cell-name {
    grid-column: 1 / -1;
    grid-row: 2;
    min-width: 0;
  }

  .cell-price {
    grid-column: 1;
    grid-row: 3;
    text-align: left !important;
    align-self: center;
  }

  .cell-actions {
    grid-column: 2;
    grid-row: 3;
  }

  .cell-actions .btn {
    height: 44px;
    padding: 0 12px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .dialog-foot .btn {
    flex: 1;
    height: 44px;
  }
}
</style>
