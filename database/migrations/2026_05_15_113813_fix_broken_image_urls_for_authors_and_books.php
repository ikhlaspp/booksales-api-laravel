<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix broken author photos (sourced from Wikipedia REST API)
        $authorUpdates = [
            1  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Andrea_Hirata.jpg/330px-Andrea_Hirata.jpg',
            4  => 'https://upload.wikimedia.org/wikipedia/commons/7/79/Pramoedya_Ananta_Toer_Kesusastraan_Indonesia_Modern_dalam_Kritik_dan_Essai_1_%281962%29_p136.jpg',
            5  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/25/Raditya_Dika_on_Interview_GoGirl_TV.jpg/330px-Raditya_Dika_on_Interview_GoGirl_TV.jpg',
            6  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Dewi_Lestari.JPG/330px-Dewi_Lestari.JPG',
            8  => 'https://upload.wikimedia.org/wikipedia/commons/f/f7/Agatha_Christie_in_Nederland_%28detectiveschrijfster%29%2C_bij_aankomst_op_Schiphol_me%2C_Bestanddeelnr_916-8898_%28cropped%29.jpg',
            9  => 'https://upload.wikimedia.org/wikipedia/commons/4/4a/Ahmad_Fuadi.jpg',
            10 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Eka_Kurniawan%2C_DSC_0071B.jpg/330px-Eka_Kurniawan%2C_DSC_0071B.jpg',
            12 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/05/George_R._R._Martin_%2854743316729%29.jpg/330px-George_R._R._Martin_%2854743316729%29.jpg',
            13 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/51/Conversatorio_Haruki_Murakami_%2812_de_12%29_%2845747009452%29_%28cropped%29.jpg/330px-Conversatorio_Haruki_Murakami_%2812_de_12%29_%2845747009452%29_%28cropped%29.jpg',
            14 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/23/COLLECTIE_TROPENMUSEUM_Portret_van_Raden_Ajeng_Kartini_TMnr_10018776.jpg/330px-COLLECTIE_TROPENMUSEUM_Portret_van_Raden_Ajeng_Kartini_TMnr_10018776.jpg',
            15 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/49/N%C3%A1v%C3%A1t%C4%9Bva_Dana_Browna_v_NK_%C4%8CR_17._-_18._2025_03_%28cropped%29.jpg/330px-N%C3%A1v%C3%A1t%C4%9Bva_Dana_Browna_v_NK_%C4%8CR_17._-_18._2025_03_%28cropped%29.jpg',
            16 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bd/Arthur_Conan_Doyle_by_Walter_Benington%2C_1914.png/330px-Arthur_Conan_Doyle_by_Walter_Benington%2C_1914.png',
            17 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/Mark_Twain_by_AF_Bradley.jpg/330px-Mark_Twain_by_AF_Bradley.jpg',
            18 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cc/CassandraAusten-JaneAusten%28c.1810%29_hires.jpg/330px-CassandraAusten-JaneAusten%28c.1810%29_hires.jpg',
            19 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Gillian_Flynn_2014_%28cropped%29.jpg/330px-Gillian_Flynn_2014_%28cropped%29.jpg',
            20 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/Fiersa_Besari_at_the_Mata_Najwa_eps._Kita_Bisa_Apa.png/330px-Fiersa_Besari_at_the_Mata_Najwa_eps._Kita_Bisa_Apa.png',
        ];

        foreach ($authorUpdates as $id => $url) {
            DB::table('authors')->where('id', $id)->update(['photo' => $url]);
        }

        // Fix broken book covers
        $bookUpdates = [
            4  => 'https://covers.openlibrary.org/b/id/15122195-L.jpg',
            5  => 'https://bukukita.com/babacms/displaybuku/6514_f.jpg',
            6  => 'https://m.media-amazon.com/images/S/compressed.photo.goodreads.com/books/1348063834i/11960914.jpg',
            9  => 'https://m.media-amazon.com/images/S/compressed.photo.goodreads.com/books/1249749162i/6688121.jpg',
            10 => 'https://upload.wikimedia.org/wikipedia/id/d/d2/CiL_%28sampul%29.jpg',
            14 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/49/Door_duisternis_tot_licht_1st_edition_cover.jpg/330px-Door_duisternis_tot_licht_1st_edition_cover.jpg',
            20 => 'https://cdn.gramedia.com/uploads/items/Garis_waktu.jpg',
        ];

        foreach ($bookUpdates as $id => $url) {
            DB::table('books')->where('id', $id)->update(['cover_photo' => $url]);
        }
    }

    public function down(): void
    {
        // Restore original (now-broken) URLs
        $authorOriginals = [
            1  => 'https://upload.wikimedia.org/wikipedia/commons/a/a8/Andrea_Hirata.jpg',
            4  => 'https://upload.wikimedia.org/wikipedia/commons/b/b3/Pramoedya_Ananta_Toer_1.jpg',
            5  => 'https://upload.wikimedia.org/wikipedia/commons/b/b0/Raditya_Dika_2017.jpg',
            6  => 'https://deelestari.com/wp-content/uploads/2018/10/Dee-Lestari-Profile.jpg',
            8  => 'https://upload.wikimedia.org/wikipedia/commons/c/cf/Agatha_Christie.jpg',
            9  => 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Ahmad_Fuadi.jpg',
            10 => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/Eka_Kurniawan_2017.jpg',
            12 => 'https://upload.wikimedia.org/wikipedia/commons/e/ed/George_R._R._Martin.jpg',
            13 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/12/Haruki_Murakami_%282023%29.jpg/800px-Haruki_Murakami_%282023%29.jpg',
            14 => 'https://upload.wikimedia.org/wikipedia/commons/b/b1/Kartini.jpg',
            15 => 'https://upload.wikimedia.org/wikipedia/commons/b/b0/Dan_Brown_-_classroom.jpg',
            16 => 'https://upload.wikimedia.org/wikipedia/commons/b/be/Conan_doyle.jpg',
            17 => 'https://upload.wikimedia.org/wikipedia/commons/0/0c/Mark_Twain_by_AF_Bradley%2C_1907_edit.jpg',
            18 => 'https://upload.wikimedia.org/wikipedia/commons/c/cc/Jane_Austen.jpg',
            19 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e3/Gillian_Flynn_by_Gage_Skidmore.jpg/800px-Gillian_Flynn_by_Gage_Skidmore.jpg',
            20 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Fiersa_Besari_at_the_2019_Indonesian_Writers_Festival.jpg/800px-Fiersa_Besari_at_the_2019_Indonesian_Writers_Festival.jpg',
        ];

        foreach ($authorOriginals as $id => $url) {
            DB::table('authors')->where('id', $id)->update(['photo' => $url]);
        }

        $bookOriginals = [
            4  => 'https://upload.wikimedia.org/wikipedia/id/0/05/Bumi_Manusia_cover.jpg',
            5  => 'https://upload.wikimedia.org/wikipedia/id/7/7d/Kambingjantan.jpg',
            6  => 'https://upload.wikimedia.org/wikipedia/id/0/01/Supernova-PARTIKEL-2012.jpg',
            9  => 'https://upload.wikimedia.org/wikipedia/id/1/15/Negeri5menara.jpg',
            10 => 'https://upload.wikimedia.org/wikipedia/id/4/47/Cantik_Itu_Luka.jpg',
            14 => 'https://upload.wikimedia.org/wikipedia/id/1/1f/Habis_Gelap_Terbitlah_Terang.jpg',
            20 => 'https://cdn.gramedia.com/uploads/items/9786027150286_Garis-Waktu.jpg',
        ];

        foreach ($bookOriginals as $id => $url) {
            DB::table('books')->where('id', $id)->update(['cover_photo' => $url]);
        }
    }
};
