=== Midtrans Standalone Payment Gateway ===
Contributors: nama-anda
Tags: midtrans, payment gateway, ecommerce, toko online, keranjang belanja, pembayaran, produk
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Solusi ecommerce lengkap dengan gateway pembayaran Midtrans, manajemen produk, dan keranjang belanja - tanpa ketergantungan WooCommerce.

== Description ==

**Midtrans Standalone Payment Gateway** adalah plugin ecommerce lengkap yang menyediakan fungsi toko online dengan integrasi pembayaran Midtrans yang mulus. Sempurna untuk toko yang menginginkan solusi ringan dan dapat disesuaikan tanpa beban WooCommerce.

= Fitur Utama =

🎯 **Solusi Ecommerce Lengkap**
* Manajemen produk dengan gambar, harga, dan inventori
* Keranjang belanja modern dengan cart mengambang
* Proses checkout pelanggan
* Manajemen pesanan

💳 **Integrasi Pembayaran Midtrans**
* Tombol pembayaran langsung
* Checkout keranjang belanja
* Pelacakan status pembayaran
* Penanganan webhook untuk update status otomatis
* Mode sandbox dan production

🛍️ **Pengalaman Belanja Modern**
* Grid produk yang indah dan responsif
* Animasi dan interaksi yang smooth
* Keranjang mengambang dengan update real-time
* Kontrol kuantitas dan aksi cepat

🎨 **Desain Profesional**
* Interface modern dan bersih
* Efek glass morphism
* Transisi dan hover effects yang smooth
* Desain fully responsive
* Skema warna yang dapat disesuaikan

= Shortcode yang Tersedia =

**Display Produk**
* `[product_list]` - Tampilkan grid produk
* `[product_list columns="4" compact="true"]` - Grid produk compact
* `[product_detail]` - Halaman single product (gunakan di halaman produk)
* `[product_detail id="123"]` - Detail produk spesifik

**Pembayaran & Checkout**
* `[midtrans_payment_form]` - Form pembayaran manual
* `[midtrans_pay_button amount="100000"]` - Tombol pembayaran langsung
* `[shopping_cart]` - Halaman keranjang belanja
* `[midtrans_payment_history]` - Riwayat pembayaran pelanggan

**Opsi Lanjutan Product List**
* `[product_list limit="12"]` - Jumlah produk
* `[product_list columns="4"]` - Kolom grid (2,3,4)
* `[product_list orderby="date"]` - Urutan sortir (date, title, price)
* `[product_list order="DESC"]` - Arah sortir
* `[product_list compact="true"]` - Layout compact

= Instalasi =

1. Upload file plugin ke direktori `/wp-content/plugins/midtrans-standalone`
2. Aktifkan plugin melalui menu 'Plugins' di WordPress
3. Buka **Midtrans Store → Settings** untuk konfigurasi API keys
4. Tambahkan produk di **Midtrans Store → Products**
5. Gunakan shortcode di halaman untuk menampilkan produk dan form pembayaran

= Konfigurasi =

**Setup API Midtrans**
1. Dapatkan Server Key dan Client Key dari [Midtrans Dashboard](https://dashboard.midtrans.com)
2. Untuk testing, gunakan environment Sandbox dan kunci Sandbox
3. Untuk production, gunakan environment Production dan kunci live

**Konfigurasi Webhook**
Set URL ini di dashboard Midtrans sebagai payment notification URL:
`https://website-anda.com/wp-admin/admin-ajax.php?action=midtrans_webhook`

**Manajemen Produk**
1. Tambahkan produk di **Midtrans Store → Products**
2. Set harga, stok, SKU, dan featured image
3. Gunakan Gutenberg editor untuk deskripsi produk

= Frequently Asked Questions =

= Bisakah saya menggunakan plugin ini tanpa WooCommerce? =
Ya! Plugin ini sepenuhnya standalone dan tidak memerlukan WooCommerce.

= Bagaimana cara menampilkan produk? =
Gunakan shortcode `[product_list]` di halaman atau post mana pun.

= Di mana pelanggan bisa melihat keranjang belanja? =
Gunakan shortcode `[shopping_cart]` di sebuah halaman, atau mereka bisa menggunakan keranjang mengambang.

= Bagaimana cara setup notifikasi pembayaran? =
Tambahkan URL webhook di dashboard Midtrans untuk otomatis update status pembayaran.

= Bisakah saya kustomisasi desain? =
Ya! Plugin menggunakan CSS variables untuk kustomisasi mudah. Override styles di theme Anda.

= Apakah responsive untuk mobile? =
Ya, desain fully responsive yang bekerja di semua device.

= Changelog =

= 2.0.0 =
* Redesain lengkap dengan UI/UX modern
* Keranjang belanja mengambang
* Enhanced product grid dengan layout compact
* Animasi dan interaksi yang smooth
* Improved responsive design
* Better error handling dan user feedback

= 1.0.0 =
* Rilis awal
* Basic product management
* Integrasi pembayaran Midtrans
* Fungsi keranjang belanja

= Upgrade Notice =

= 2.0.0 =
Update major dengan redesain lengkap dan fitur enhanced. Direkomendasikan untuk semua user.

= Support =

Untuk support dan dokumentasi, silakan kunjungi [GitHub repository](https://github.com/your-repo/midtrans-standalone) atau hubungi melalui forum support WordPress.

= Credits =

* API Midtrans untuk processing pembayaran
* Teknik CSS modern untuk styling
* Komunitas WordPress untuk best practices

== Screenshots ==

1. Grid produk modern dengan layout compact
2. Keranjang belanja dengan icon cart mengambang
3. Halaman detail produk dengan image gallery
4. Form pembayaran dengan integrasi Midtrans
5. Interface admin manajemen produk
6. Riwayat transaksi dan reporting

== License ==

Plugin ini dilisensikan di bawah GPL v2 atau later.
