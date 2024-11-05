# Project Klinik Jogjacamp Test
Pada awalnya masuk menggunakan cd ke dalam project di terminal.
Kemudian untuk pindahkan .env.example ke env sesuaikan koneksi db yang ada.

Jika sudah lakukan migrasi dengan menggunakan:
```
php artisan migrate
```

untuk nanti seeder setelah melakukan testing dapat mengeksekusi ini:
```
php artisan db:seed
```

untuk memulai testing
```
php artisan test
```

jika ingin melakukan testing secara mandiri 
```
php artisan serve
php artisan queue:work
```

## Penjelasan fungsi
Dalam project ini terdapat 6 endpoint yang digunakan berikut ini merupakan penjelasan singkat mengenai setiap endpoint:
### 1. Create Patient
```
POST http://127.0.0.1:8000/api/patient
```
Endpoint ini digunakan untuk mendaftarkan pasien baru.
berikut ini merupakan contoh request body:
```
{
    "name": "gema"
}
```
pada endpoint ini akan melakukan validasi sebelum memproses lebih lanjut yaitu:
- jika tidak ada request nama
- dan nama sudah exist di database

### 2. Create Service
```
POST http://127.0.0.1:8000/api/service
```
Endpoint ini digunakan untuk mendaftarkan service baru pada klinik.
```
{
    "name": "icu"
}
```
pada endpoint ini akan melakukan validasi sebelum memproses lebih lanjut yaitu:
- jika tidak ada request nama
- dan nama sudah exist di database
### 3. Create Diagnose
```
POST http://127.0.0.1:8000/api/diagnose
```
Endpoint ini digunakan untuk mendaftarkan diagnosa level sakit pasien.
```
{
    "name": "kritis"
}
```
pada endpoint ini akan melakukan validasi sebelum memproses lebih lanjut yaitu:
- jika tidak ada request nama
- dan nama sudah exist di database

### 4. Create Appointment
```
POST http://127.0.0.1:8000/api/appointment
```
Endpoint ini digunakan untuk mendaftarkan diagnosa level sakit pasien.
```
{
    "patient_id": 1,
    "diagnose_id": 3
}
```
pada endpoint ini akan melakukan validasi sebelum memproses lebih lanjut yaitu:
- jika tidak ada request patient_id dan diagnose_id
- cek existing atau tidaknya di database

Terdapat beberapa kondisi appointment berdasarkan level sakit pasien yang dirawat, berikut ini merupakan beberapa point kondisi:
- kondisi jika sakit ringan service yang diberikan adalah obat
- kondisi jika sakit berat service yang diberikan adalah obat dan rawat inap
- kondisi jika sakit berat service yang diberikan adalah obat, rawat inap, dan icu
