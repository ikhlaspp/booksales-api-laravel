<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Budi Santoso', 'email' => 'budi@email.com', 'password' => Hash::make('password123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 2, 'name' => 'Siti Aminah', 'email' => 'siti@email.com', 'password' => Hash::make('rahasia321'), 'role' => 'user', 'last_access' => $now],
            ['id' => 3, 'name' => 'Admin Utama', 'email' => 'admin@toko.com', 'password' => Hash::make('adminpass'), 'role' => 'admin', 'last_access' => $now],
            ['id' => 4, 'name' => 'Rina Melati', 'email' => 'rina@email.com', 'password' => Hash::make('passrina'), 'role' => 'user', 'last_access' => $now],
            ['id' => 5, 'name' => 'Andi Wijaya', 'email' => 'andi@email.com', 'password' => Hash::make('andi1234'), 'role' => 'user', 'last_access' => $now],
            ['id' => 6, 'name' => 'Citra Kirana', 'email' => 'citra@email.com', 'password' => Hash::make('citra123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 7, 'name' => 'Dodi Pratama', 'email' => 'dodi@email.com', 'password' => Hash::make('dodi123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 8, 'name' => 'Eka Putri', 'email' => 'eka@email.com', 'password' => Hash::make('eka123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 9, 'name' => 'Faisal Reza', 'email' => 'faisal@email.com', 'password' => Hash::make('faisal123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 10, 'name' => 'Admin Kedua', 'email' => 'admin2@toko.com', 'password' => Hash::make('adminpass2'), 'role' => 'admin', 'last_access' => $now],
            ['id' => 11, 'name' => 'Galih Purnomo', 'email' => 'galih@email.com', 'password' => Hash::make('galih123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 12, 'name' => 'Hani Syaputri', 'email' => 'hani@email.com', 'password' => Hash::make('hani123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 13, 'name' => 'Irfan Hakim', 'email' => 'irfan@email.com', 'password' => Hash::make('irfan123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 14, 'name' => 'Julia Perez', 'email' => 'julia@email.com', 'password' => Hash::make('julia123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 15, 'name' => 'Kevin Sanjaya', 'email' => 'kevin@email.com', 'password' => Hash::make('kevin123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 16, 'name' => 'Lestari Indah', 'email' => 'lestari@email.com', 'password' => Hash::make('lestari123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 17, 'name' => 'Mamat Alkatiri', 'email' => 'mamat@email.com', 'password' => Hash::make('mamat123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 18, 'name' => 'Nia Ramadhani', 'email' => 'nia@email.com', 'password' => Hash::make('nia123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 19, 'name' => 'Oki Setiana', 'email' => 'oki@email.com', 'password' => Hash::make('oki123'), 'role' => 'user', 'last_access' => $now],
            ['id' => 20, 'name' => 'Pandu Winata', 'email' => 'pandu@email.com', 'password' => Hash::make('pandu123'), 'role' => 'user', 'last_access' => $now],
        ]);

        DB::table('genres')->insert([
            ['id' => 1, 'name' => 'Fiksi', 'description' => 'Cerita rekaan yang tidak berdasarkan kenyataan sejarah.'],
            ['id' => 2, 'name' => 'Fantasi', 'description' => 'Genre yang menggunakan sihir atau elemen supranatural.'],
            ['id' => 3, 'name' => 'Komedi', 'description' => 'Genre yang bertujuan untuk memancing tawa pembaca.'],
            ['id' => 4, 'name' => 'Biografi', 'description' => 'Kisah perjalanan hidup seseorang tokoh.'],
            ['id' => 5, 'name' => 'Sejarah', 'description' => 'Cerita yang berlatar belakang peristiwa sejarah masa lampau.'],
            ['id' => 6, 'name' => 'Horor', 'description' => 'Cerita yang bertujuan menimbulkan rasa takut.'],
            ['id' => 7, 'name' => 'Romansa', 'description' => 'Kisah yang berfokus pada hubungan asmara.'],
            ['id' => 8, 'name' => 'Misteri', 'description' => 'Kisah tentang pemecahan teka-teki atau kejahatan.'],
            ['id' => 9, 'name' => 'Thriler', 'description' => 'Kisah yang menegangkan dan memacu adrenalin.'],
            ['id' => 10, 'name' => 'Sains', 'description' => 'Buku yang membahas pengetahuan ilmiah secara faktual atau fiksi.'],
            ['id' => 11, 'name' => 'Petualangan', 'description' => 'Kisah tentang sebuah perjalanan heroik.'],
            ['id' => 12, 'name' => 'Puisi', 'description' => 'Karya sastra yang mengutamakan keindahan bahasa.'],
            ['id' => 13, 'name' => 'Dongeng', 'description' => 'Cerita fiktif tentang hal-hal gaib.'],
            ['id' => 14, 'name' => 'Distopia', 'description' => 'Cerita tentang dunia yang kacau dan menakutkan.'],
            ['id' => 15, 'name' => 'Esei', 'description' => 'Karangan prosa yang membahas suatu masalah.'],
            ['id' => 16, 'name' => 'Agama', 'description' => 'Buku yang membahas tentang ajaran religius.'],
            ['id' => 17, 'name' => 'Filsafat', 'description' => 'Buku mengenai pemikiran dan kebijaksanaan.'],
            ['id' => 18, 'name' => 'Psikologi', 'description' => 'Buku yang membahas kejiwaan dan perilaku.'],
            ['id' => 19, 'name' => 'Hukum', 'description' => 'Buku yang berkaitan dengan sistem peradilan.'],
            ['id' => 20, 'name' => 'Ekonomi', 'description' => 'Buku seputar keuangan, bisnis, dan perdagangan.'],
        ]);

        DB::table('authors')->insert([
            ['id' => 1, 'name' => 'Andrea Hirata', 'photo' => 'andrea.jpg', 'bio' => 'Penulis novel Laskar Pelangi.'],
            ['id' => 2, 'name' => 'Tere Liye', 'photo' => 'tere.jpg', 'bio' => 'Penulis buku fiksi populer Indonesia.'],
            ['id' => 3, 'name' => 'J.K. Rowling', 'photo' => 'jk.jpg', 'bio' => 'Penulis seri Harry Potter.'],
            ['id' => 4, 'name' => 'Pramoedya Ananta Toer', 'photo' => 'pram.jpg', 'bio' => 'Sastrawan besar Indonesia.'],
            ['id' => 5, 'name' => 'Raditya Dika', 'photo' => 'raditya.jpg', 'bio' => 'Penulis buku komedi dan sutradara.'],
            ['id' => 6, 'name' => 'Dee Lestari', 'photo' => 'dee.jpg', 'bio' => 'Penulis novel Supernova.'],
            ['id' => 7, 'name' => 'Stephen King', 'photo' => 'stephen.jpg', 'bio' => 'Raja novel horor dan supranatural dunia.'],
            ['id' => 8, 'name' => 'Agatha Christie', 'photo' => 'agatha.jpg', 'bio' => 'Ratu novel misteri detektif asal Inggris.'],
            ['id' => 9, 'name' => 'Ahmad Fuadi', 'photo' => 'ahmad.jpg', 'bio' => 'Penulis novel Negeri 5 Menara.'],
            ['id' => 10, 'name' => 'Eka Kurniawan', 'photo' => 'eka_kur.jpg', 'bio' => 'Novelis dari berbagai genre seperti fiksi sejarah.'],
            ['id' => 11, 'name' => 'J.R.R. Tolkien', 'photo' => 'tolkien.jpg', 'bio' => 'Penulis The Lord of the Rings.'],
            ['id' => 12, 'name' => 'George R.R. Martin', 'photo' => 'martin.jpg', 'bio' => 'Penulis seri A Song of Ice and Fire.'],
            ['id' => 13, 'name' => 'Haruki Murakami', 'photo' => 'haruki.jpg', 'bio' => 'Penulis novel surealis dari Jepang.'],
            ['id' => 14, 'name' => 'R.A. Kartini', 'photo' => 'kartini.jpg', 'bio' => 'Pahlawan nasional dan penulis surat-surat.'],
            ['id' => 15, 'name' => 'Dan Brown', 'photo' => 'danbrown.jpg', 'bio' => 'Penulis novel The Da Vinci Code.'],
            ['id' => 16, 'name' => 'Arthur Conan Doyle', 'photo' => 'arthur.jpg', 'bio' => 'Pencipta karakter Sherlock Holmes.'],
            ['id' => 17, 'name' => 'Mark Twain', 'photo' => 'mark.jpg', 'bio' => 'Penulis petualangan Amerika klasik.'],
            ['id' => 18, 'name' => 'Jane Austen', 'photo' => 'janeausten.jpg', 'bio' => 'Penulis novel romantis klasik Inggris.'],
            ['id' => 19, 'name' => 'Gillian Flynn', 'photo' => 'gillian.jpg', 'bio' => 'Penulis novel thriller fenomenal.'],
            ['id' => 20, 'name' => 'Fiersa Besari', 'photo' => 'fiersa.jpg', 'bio' => 'Musisi dan penulis buku romansa petualangan.'],
        ]);

        DB::table('books')->insert([
            ['id' => 1, 'title' => 'Laskar Pelangi', 'description' => 'Kisah 10 anak di Belitung.', 'price' => 80000.00, 'stock' => 29, 'cover_photo' => 'https://upload.wikimedia.org/wikipedia/id/8/8e/Laskar_pelangi_sampul.jpg', 'genre_id' => 1, 'author_id' => 1],
            ['id' => 2, 'title' => 'Bumi', 'description' => 'Petualangan Raib di dunia paralel.', 'price' => 85000.00, 'stock' => 15, 'cover_photo' => 'https://upload.wikimedia.org/wikipedia/id/4/49/Bumi_%28sampul%29.jpg', 'genre_id' => 2, 'author_id' => 2],
            ['id' => 3, 'title' => 'Harry Potter dan Batu Bertuah', 'description' => 'Awal kisah penyihir cilik.', 'price' => 150000.00, 'stock' => 20, 'cover_photo' => 'https://covers.openlibrary.org/b/id/10580458-L.jpg', 'genre_id' => 2, 'author_id' => 3],
            ['id' => 4, 'title' => 'Bumi Manusia', 'description' => 'Kisah cinta Minke dan Annelies.', 'price' => 120000.00, 'stock' => 8, 'cover_photo' => 'https://upload.wikimedia.org/wikipedia/id/0/05/Bumi_Manusia_cover.jpg', 'genre_id' => 5, 'author_id' => 4],
            ['id' => 5, 'title' => 'Kambing Jantan', 'description' => 'Catatan harian pelajar bodoh.', 'price' => 60000.00, 'stock' => 24, 'cover_photo' => 'https://upload.wikimedia.org/wikipedia/id/7/7d/Kambingjantan.jpg', 'genre_id' => 3, 'author_id' => 5],
            ['id' => 6, 'title' => 'Supernova', 'description' => 'Fiksi sains tentang dinamika romansa.', 'price' => 95000.00, 'stock' => 12, 'cover_photo' => 'https://upload.wikimedia.org/wikipedia/id/0/01/Supernova-PARTIKEL-2012.jpg', 'genre_id' => 10, 'author_id' => 6],
            ['id' => 7, 'title' => 'The Shining', 'description' => 'Kisah keluarga yang terjebak di hotel angker.', 'price' => 110000.00, 'stock' => 5, 'cover_photo' => 'https://covers.openlibrary.org/b/id/12376585-L.jpg', 'genre_id' => 6, 'author_id' => 7],
            ['id' => 8, 'title' => 'Murder on the Orient Express', 'description' => 'Pembunuhan di atas kereta.', 'price' => 105000.00, 'stock' => 18, 'cover_photo' => 'https://covers.openlibrary.org/b/id/11100465-L.jpg', 'genre_id' => 8, 'author_id' => 8],
            ['id' => 9, 'title' => 'Negeri 5 Menara', 'description' => 'Kisah 6 santri yang mengejar impian.', 'price' => 88000.00, 'stock' => 30, 'cover_photo' => 'https://upload.wikimedia.org/wikipedia/id/1/15/Negeri5menara.jpg', 'genre_id' => 1, 'author_id' => 9],
            ['id' => 10, 'title' => 'Cantik Itu Luka', 'description' => 'Realisme magis sejarah epik.', 'price' => 125000.00, 'stock' => 10, 'cover_photo' => 'https://upload.wikimedia.org/wikipedia/id/4/47/Cantik_Itu_Luka.jpg', 'genre_id' => 5, 'author_id' => 10],
            ['id' => 11, 'title' => 'The Lord of the Rings', 'description' => 'Cerita epik para pembawa cincin.', 'price' => 250000.00, 'stock' => 22, 'cover_photo' => 'https://covers.openlibrary.org/b/id/14625765-L.jpg', 'genre_id' => 2, 'author_id' => 11],
            ['id' => 12, 'title' => 'A Game of Thrones', 'description' => 'Perebutan takhta di benua Westeros.', 'price' => 180000.00, 'stock' => 14, 'cover_photo' => 'https://covers.openlibrary.org/b/id/9269962-L.jpg', 'genre_id' => 2, 'author_id' => 12],
            ['id' => 13, 'title' => 'Norwegian Wood', 'description' => 'Kisah cinta yang muram bergaya surealis.', 'price' => 90000.00, 'stock' => 16, 'cover_photo' => 'https://covers.openlibrary.org/b/id/2237620-L.jpg', 'genre_id' => 7, 'author_id' => 13],
            ['id' => 14, 'title' => 'Habis Gelap Terbitlah Terang', 'description' => 'Kumpulan surat perjuangan memajukan wanita.', 'price' => 70000.00, 'stock' => 35, 'cover_photo' => 'https://upload.wikimedia.org/wikipedia/id/1/1f/Habis_Gelap_Terbitlah_Terang.jpg', 'genre_id' => 4, 'author_id' => 14],
            ['id' => 15, 'title' => 'The Da Vinci Code', 'description' => 'Pencarian cawan suci dan teori konspirasi.', 'price' => 135000.00, 'stock' => 19, 'cover_photo' => 'https://covers.openlibrary.org/b/id/9255229-L.jpg', 'genre_id' => 8, 'author_id' => 15],
            ['id' => 16, 'title' => 'Sherlock Holmes', 'description' => 'Petualangan detektif paling terkenal.', 'price' => 115000.00, 'stock' => 25, 'cover_photo' => 'https://covers.openlibrary.org/b/id/6717853-L.jpg', 'genre_id' => 8, 'author_id' => 16],
            ['id' => 17, 'title' => 'The Adventures of Tom Sawyer', 'description' => 'Kisah seorang anak lelaki yang cerdik.', 'price' => 75000.00, 'stock' => 11, 'cover_photo' => 'https://covers.openlibrary.org/b/id/12043351-L.jpg', 'genre_id' => 11, 'author_id' => 17],
            ['id' => 18, 'title' => 'Pride and Prejudice', 'description' => 'Kritik sosial tentang pernikahan di Inggris.', 'price' => 85000.00, 'stock' => 21, 'cover_photo' => 'https://covers.openlibrary.org/b/id/14348537-L.jpg', 'genre_id' => 7, 'author_id' => 18],
            ['id' => 19, 'title' => 'Gone Girl', 'description' => 'Kisah istri yang menghilang misterius.', 'price' => 95000.00, 'stock' => 17, 'cover_photo' => 'https://covers.openlibrary.org/b/id/8368314-L.jpg', 'genre_id' => 9, 'author_id' => 19],
            ['id' => 20, 'title' => 'Garis Waktu', 'description' => 'Cerita kenangan dan waktu.', 'price' => 65000.00, 'stock' => 28, 'cover_photo' => 'https://cdn.gramedia.com/uploads/items/9786027150286_Garis-Waktu.jpg', 'genre_id' => 7, 'author_id' => 20],
        ]);

        DB::table('transactions')->insert([
            ['id' => 1, 'order_number' => 'ORD-001', 'customer_id' => 1, 'book_id' => 1, 'total_amount' => 75000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'order_number' => 'ORD-002', 'customer_id' => 2, 'book_id' => 3, 'total_amount' => 150000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'order_number' => 'ORD-003', 'customer_id' => 4, 'book_id' => 2, 'total_amount' => 85000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'order_number' => 'ORD-004', 'customer_id' => 5, 'book_id' => 5, 'total_amount' => 60000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'order_number' => 'ORD-005', 'customer_id' => 6, 'book_id' => 6, 'total_amount' => 95000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'order_number' => 'ORD-006', 'customer_id' => 1, 'book_id' => 1, 'total_amount' => 80000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'order_number' => 'ORD-007', 'customer_id' => 7, 'book_id' => 7, 'total_amount' => 110000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'order_number' => 'ORD-999', 'customer_id' => 4, 'book_id' => 5, 'total_amount' => 60000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'order_number' => 'ORD-008', 'customer_id' => 8, 'book_id' => 8, 'total_amount' => 105000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'order_number' => 'ORD-009', 'customer_id' => 9, 'book_id' => 10, 'total_amount' => 125000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'order_number' => 'ORD-010', 'customer_id' => 11, 'book_id' => 11, 'total_amount' => 250000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'order_number' => 'ORD-011', 'customer_id' => 12, 'book_id' => 12, 'total_amount' => 180000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'order_number' => 'ORD-012', 'customer_id' => 13, 'book_id' => 13, 'total_amount' => 90000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'order_number' => 'ORD-013', 'customer_id' => 14, 'book_id' => 14, 'total_amount' => 70000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'order_number' => 'ORD-014', 'customer_id' => 15, 'book_id' => 15, 'total_amount' => 135000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'order_number' => 'ORD-015', 'customer_id' => 16, 'book_id' => 16, 'total_amount' => 115000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 17, 'order_number' => 'ORD-016', 'customer_id' => 17, 'book_id' => 17, 'total_amount' => 75000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 18, 'order_number' => 'ORD-017', 'customer_id' => 18, 'book_id' => 18, 'total_amount' => 85000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 19, 'order_number' => 'ORD-018', 'customer_id' => 19, 'book_id' => 19, 'total_amount' => 95000.00, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'order_number' => 'ORD-019', 'customer_id' => 20, 'book_id' => 20, 'total_amount' => 65000.00, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}

