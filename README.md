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
