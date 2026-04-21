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
            ['id' => 1, 'name' => 'Budi Santoso', 'email' => 'budi@email.com', 'password' => Hash::make('password123'), 'role' => 'customer', 'last_access' => $now],
            ['id' => 2, 'name' => 'Siti Aminah', 'email' => 'siti@email.com', 'password' => Hash::make('rahasia321'), 'role' => 'customer', 'last_access' => $now],
            ['id' => 3, 'name' => 'Admin Utama', 'email' => 'admin@toko.com', 'password' => Hash::make('adminpass'), 'role' => 'admin', 'last_access' => $now],
            ['id' => 4, 'name' => 'Rina Melati', 'email' => 'rina@email.com', 'password' => Hash::make('passrina'), 'role' => 'customer', 'last_access' => $now],
            ['id' => 5, 'name' => 'Andi Wijaya', 'email' => 'andi@email.com', 'password' => Hash::make('andi1234'), 'role' => 'customer', 'last_access' => $now],
            ['id' => 6, 'name' => 'Citra Kirana', 'email' => 'citra@email.com', 'password' => Hash::make('citra123'), 'role' => 'customer', 'last_access' => $now],
            ['id' => 7, 'name' => 'Dodi Pratama', 'email' => 'dodi@email.com', 'password' => Hash::make('dodi123'), 'role' => 'customer', 'last_access' => $now],
            ['id' => 8, 'name' => 'Eka Putri', 'email' => 'eka@email.com', 'password' => Hash::make('eka123'), 'role' => 'customer', 'last_access' => $now],
            ['id' => 9, 'name' => 'Faisal Reza', 'email' => 'faisal@email.com', 'password' => Hash::make('faisal123'), 'role' => 'customer', 'last_access' => $now],
            ['id' => 10, 'name' => 'Admin Kedua', 'email' => 'admin2@toko.com', 'password' => Hash::make('adminpass2'), 'role' => 'admin', 'last_access' => $now],
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
        ]);

        DB::table('books')->insert([
            ['id' => 1, 'title' => 'Laskar Pelangi', 'description' => 'Kisah 10 anak di Belitung.', 'price' => 80000.00, 'stock' => 29, 'cover_photo' => 'laskar.jpg', 'genre_id' => 1, 'author_id' => 1],
            ['id' => 2, 'title' => 'Bumi', 'description' => 'Petualangan Raib di dunia paralel.', 'price' => 85000.00, 'stock' => 15, 'cover_photo' => 'bumi.jpg', 'genre_id' => 2, 'author_id' => 2],
            ['id' => 3, 'title' => 'Harry Potter dan Batu Bertuah', 'description' => 'Awal kisah penyihir cilik.', 'price' => 150000.00, 'stock' => 20, 'cover_photo' => 'hp1.jpg', 'genre_id' => 2, 'author_id' => 3],
            ['id' => 4, 'title' => 'Bumi Manusia', 'description' => 'Kisah cinta Minke dan Annelies.', 'price' => 120000.00, 'stock' => 8, 'cover_photo' => 'bumimanusia.jpg', 'genre_id' => 5, 'author_id' => 4],
            ['id' => 5, 'title' => 'Kambing Jantan', 'description' => 'Catatan harian pelajar bodoh.', 'price' => 60000.00, 'stock' => 24, 'cover_photo' => 'kambing.jpg', 'genre_id' => 3, 'author_id' => 5],
            ['id' => 6, 'title' => 'Supernova', 'description' => 'Fiksi sains tentang dinamika romansa.', 'price' => 95000.00, 'stock' => 12, 'cover_photo' => 'supernova.jpg', 'genre_id' => 10, 'author_id' => 6],
            ['id' => 7, 'title' => 'The Shining', 'description' => 'Kisah keluarga yang terjebak di hotel angker.', 'price' => 110000.00, 'stock' => 5, 'cover_photo' => 'shining.jpg', 'genre_id' => 6, 'author_id' => 7],
            ['id' => 8, 'title' => 'Murder on the Orient Express', 'description' => 'Pembunuhan di atas kereta.', 'price' => 105000.00, 'stock' => 18, 'cover_photo' => 'orient.jpg', 'genre_id' => 8, 'author_id' => 8],
            ['id' => 9, 'title' => 'Negeri 5 Menara', 'description' => 'Kisah 6 santri yang mengejar impian.', 'price' => 88000.00, 'stock' => 30, 'cover_photo' => 'menara.jpg', 'genre_id' => 1, 'author_id' => 9],
            ['id' => 10, 'title' => 'Cantik Itu Luka', 'description' => 'Realisme magis sejarah epik.', 'price' => 125000.00, 'stock' => 10, 'cover_photo' => 'cantik.jpg', 'genre_id' => 5, 'author_id' => 10],
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
        ]);
    }
}

