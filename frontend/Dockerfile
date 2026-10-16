# Stage 1: Tahap build menggunakan Node.js
FROM node:20-alpine AS builder

WORKDIR /app

# Salin package manifest terlebih dahulu untuk optimasi layer cache
COPY package*.json ./

# Install dependensi dengan npm ci
RUN npm ci

# Salin seluruh kode frontend
COPY . .

# Jalankan build produksi (menghasilkan folder dist)
RUN npm run build

# Stage 2: Tahap penyajian menggunakan web server Nginx
FROM nginx:1.27-alpine

# Hapus konten default Nginx
RUN rm -rf /usr/share/nginx/html/*

# Salin konfigurasi Nginx SPA
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Salin aset statis hasil build dari stage builder (tidak menyalin node_modules)
COPY --from=builder /app/dist /usr/share/nginx/html

# Expose port web server
EXPOSE 80

# Jalankan Nginx secara foreground
CMD ["nginx", "-g", "daemon off;"]
