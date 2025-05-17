# Task Management System

**Sistem Manajemen Tugas** berbasis Laravel dengan fitur autentikasi, manajemen tugas, pemeriksaan tugas terlambat, dan ekspor data ke CSV.

## 📋 Daftar Isi
1. [Fitur](#-fitur)
2. [Teknologi](#-teknologi)
3. [Instalasi](#-instalasi)
4. [Konfigurasi](#-konfigurasi)
5. [Struktur Database & ERD](#-struktur-database--erd)
6. [Penggunaan](#-penggunaan)
7. [Screenshot](#-screenshot)

## ✨ Fitur
✅ **Autentikasi Pengguna**
- Login & Logout
- Proteksi route dengan middleware

✅ **Manajemen Tugas (CRUD)**
- Buat, lihat, edit, hapus tugas
- Filter tugas berdasarkan status

✅ **Pemeriksaan Tugas Terlambat**
- Otomatis menandai tugas yang melewati `due_date`
- Dijalankan via Laravel Scheduler


## 🛠 Teknologi
- **Backend:** Laravel 10
- **Database:** MySQL
- **Frontend:** VanillaJS
- **Testing:** PHPUnit

## ⚙ Instalasi

### Persyaratan
- PHP ≥ 8.4
- Composer
- MySQL ≥ 8.1

### Langkah-langkah
1. **Clone repositori**
2. **install dependencies**
3. **Setup enivronment**
4. **konfigurasi database .env**
5. **jalankan migrasi dan seeder**
6. **menjalankan aplikasi**

### Struktur Database 
- Users (id, name, email, password, role, is_active)
- Tasks (id, user_id, title, description, due_date, status, is_overdue)
- ActivityLog (id, user_id, description, status, action)

### Penggunaan 
1. **login admin**
- Email: admin@example
- Password:password123

1. **login manager**
- Email: manager@example
- Password:password123

1. **login staff**
- Email: staff@example
- Password:password123 