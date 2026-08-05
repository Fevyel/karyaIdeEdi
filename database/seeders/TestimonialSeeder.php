<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TestimonialSeeder extends Seeder
{
    /**
     * Dummy komentar untuk alur "Interaksi" (belum ada sistem pelanggan
     * sungguhan). Begitu sistem pelanggan selesai nanti, komentar asli
     * akan mengalir ke tabel yang sama ini (approval_status = pending),
     * lalu admin tinggal approve/reject seperti biasa lewat menu Interaksi.
     *
     * 5 approved (tampil di frontend), 3 pending, 2 rejected.
     * Aman dijalankan berkali-kali — hapus dulu baris dummy sebelumnya
     * (ditandai lewat kolom `jabatan` yang dipakai di sini) baru insert ulang.
     */
    public function run(): void
    {
        $dummies = [
            [
                'customer_name' => 'Budi Santoso',
                'jabatan' => 'Arsitek',
                'rating' => 5,
                'comment' => 'Sofa custom dari Karya Ide Edi hasilnya rapi banget, jahitannya kuat dan busanya empuk tapi tetap padat. Proses pemesanan sampai selesai juga jelas, admin responsif waktu saya tanya-tanya ukuran.',
                'approval_status' => 'approved',
                'days_ago' => 2,
                'featured_home' => true,
            ],
            [
                'customer_name' => 'Rina Maharani',
                'jabatan' => 'Interior Designer',
                'rating' => 5,
                'comment' => 'Sering rekomendasikan Karya Ide Edi ke klien saya karena finishing kayunya halus dan warnanya konsisten sesuai contoh. Meja makan yang saya pesan datang tepat waktu dan packingnya aman.',
                'approval_status' => 'approved',
                'days_ago' => 5,
                'featured_home' => true,
            ],
            [
                'customer_name' => 'Ahmad Fauzi',
                'jabatan' => 'Pengusaha',
                'rating' => 4,
                'comment' => 'Kualitas lemarinya bagus, kokoh dan engselnya halus. Cuma waktu pengirimannya agak molor beberapa hari dari perkiraan awal. Selebihnya puas dengan hasil akhirnya.',
                'approval_status' => 'approved',
                'days_ago' => 9,
                'featured_home' => true,
            ],
            [
                'customer_name' => 'Dinda Putri',
                'jabatan' => 'Ibu Rumah Tangga',
                'rating' => 5,
                'comment' => 'Rak dapur yang saya beli pas banget sama ukuran ruangan, katanya bisa custom jadi memang dipastikan dulu sama tim mereka sebelum produksi. Anak-anak juga suka warnanya.',
                'approval_status' => 'approved',
                'days_ago' => 13,
            ],
            [
                'customer_name' => 'Yoga Pratama',
                'jabatan' => 'Karyawan Swasta',
                'rating' => 4,
                'comment' => 'Tempat tidur kayu jatinya kokoh, nggak ada bunyi decit sama sekali. Harganya juga masih masuk akal dibanding toko sebelah untuk kualitas serupa. Bakal order lagi buat kamar anak.',
                'approval_status' => 'approved',
                'days_ago' => 18,
            ],
            [
                'customer_name' => 'Siti Nurhaliza',
                'jabatan' => 'Guru',
                'rating' => 5,
                'comment' => 'Kursi belajar buat anak saya modelnya lucu dan kuat, cocok buat dipakai tiap hari. Baru sampai kemarin jadi belum saya kasih ulasan panjang, tapi kesan pertamanya bagus.',
                'approval_status' => 'pending',
                'days_ago' => 1,
            ],
            [
                'customer_name' => 'Bambang Wijaya',
                'jabatan' => 'Wiraswasta',
                'rating' => 4,
                'comment' => 'Meja kerja yang saya pesan sesuai gambar di katalog, permukaannya halus dan nggak gampang baret. Pengirimannya juga aman, tidak ada bagian yang lecet waktu dibongkar.',
                'approval_status' => 'pending',
                'days_ago' => 1,
            ],
            [
                'customer_name' => 'Maya Anggraini',
                'jabatan' => 'Dokter',
                'rating' => 5,
                'comment' => 'Set kursi ruang tamu nyaman dipakai duduk lama, busanya pas nggak kekerasan atau kelembekan. Warnanya juga sama persis kayak yang saya lihat di foto produk.',
                'approval_status' => 'pending',
                'days_ago' => 2,
            ],
            [
                'customer_name' => 'Fajar Ramadhan',
                'jabatan' => 'Konsultan',
                'rating' => 4,
                'comment' => 'Lemari pakaiannya oke, tapi komentar ini saya kirim dua kali karena kepencet tombolnya. Yang ini duplikat punya saya sendiri, boleh diabaikan admin.',
                'approval_status' => 'rejected',
                'days_ago' => 7,
            ],
            [
                'customer_name' => 'Lestari Wulandari',
                'jabatan' => 'Freelancer',
                'rating' => 4,
                'comment' => 'Rak sepatunya bagus, tapi komentar saya ini isinya lebih ke pertanyaan soal cara perawatan kayunya, bukan ulasan produk. Tolong dijawab lewat WhatsApp saja ya.',
                'approval_status' => 'rejected',
                'days_ago' => 11,
            ],
        ];

        // Hapus dummy lama dulu (ditandai lewat nama + jabatan yang sama persis
        // seperti daftar di atas), supaya seeder aman dijalankan berkali-kali
        // tanpa menumpuk data duplikat.
        $names = collect($dummies)->pluck('customer_name');
        Testimonial::query()->whereIn('customer_name', $names)->whereNull('product_id')->delete();

        foreach ($dummies as $dummy) {
            $testimonial = new Testimonial();
            $testimonial->customer_name = $dummy['customer_name'];
            $testimonial->jabatan = $dummy['jabatan'];
            $testimonial->rating = $dummy['rating'];
            $testimonial->comment = $dummy['comment'];
            $testimonial->approval_status = $dummy['approval_status'];
            $testimonial->is_active = $dummy['approval_status'] === 'approved';
            $testimonial->is_featured_home = $dummy['approval_status'] === 'approved' && ($dummy['featured_home'] ?? false);
            $testimonial->urutan = 0;

            $timestamp = Carbon::now()->subDays($dummy['days_ago']);
            $testimonial->created_at = $timestamp;
            $testimonial->updated_at = $timestamp;

            $testimonial->save();
        }
    }
}
