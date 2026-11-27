<?php

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/BeritaModel.php';
require_once __DIR__ . '/../Services/DynamicTable/DynamicSanitizer.php';

use App\Services\DynamicTable\DynamicSanitizer;

class HomeController extends Controller
{
    public function spa()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . app_url('/'));
            exit;
        }

        require '../app/Views/layouts/app.php';
    }

    public function index()
    {
        $this->home();
    }

    public function home()
    {
        $this->portal('home');
    }

    public function berita()
    {
        $this->news();
    }

    public function news()
    {
        $this->portal('berita');
    }

    public function datateknis()
    {
        $this->portal('data_teknis');
    }

    public function organisasi()
    {
        $this->portal('organisasi');
    }

    public function pelayanan()
    {
        $this->portal('pelayanan');
    }

    private function portal(string $active): void
    {
        $pages = [
            'home' => ['/', 'Beranda', 'home', ''],
            'berita' => ['/berita', 'Berita', 'newspaper outline', 'Kabar dan artikel tentang infrastruktur, organisasi, data, dan layanan di Pasangkayu.'],
            'data_teknis' => ['/datateknis', 'Data Teknis', 'chart bar outline', 'Telusuri realisasi jalan, air minum, sanitasi, irigasi, drainase, dan tata ruang.'],
            'organisasi' => ['/organisasi', 'Struktur Organisasi', 'sitemap', 'Kenali susunan Dinas PUPR Pasangkayu dan lingkup tugas setiap bidang.'],
            'pelayanan' => ['/pelayanan', 'Pelayanan', 'handshake outline', 'Temukan layanan alat berat, laboratorium konstruksi, rekomendasi tata ruang, air bersih, dan penyedotan tangki septik.'],
        ];

        $sectionCovers = [
            'home' => '/assets/img/portal/bundaran-smart-konsep.webp',
            'berita' => '/assets/img/portal/realisasi-pupr.webp',
            'data_teknis' => '/assets/img/portal/sawah-bambalamotu.webp',
            'organisasi' => '/assets/img/portal/kantor-dinas-pupr-konsep.webp',
            'pelayanan' => '/assets/img/portal/meja-layanan.webp',
        ];
        $leadershipOrder = array_flip(['bupati-pasangkayu', 'wakil-bupati-pasangkayu', 'kepala-dinas-pupr-pasangkayu']);
        $regionalProfileSlug = 'pimpinan-daerah-pasangkayu';
        $isLeadership = static fn (?string $slug): bool => $slug !== null
            && (isset($leadershipOrder[$slug]) || $slug === $regionalProfileSlug);
        $serviceOrder = array_flip([
            'sewa-alat-berat',
            'laboratorium-konstruksi',
            'rekomendasi-tata-ruang',
            'uptd-pengelolaan-air-bersih',
            'uptd-sanitasi-sedot-tinja',
        ]);

        $simulation = ($_GET['simulasi'] ?? null) === '1';
        $pageUrl = static function (string $page, array $query = []) use ($pages, $simulation): string {
            if ($simulation) {
                $query['simulasi'] = '1';
            }
            return app_url($pages[$page][0]) . ($query ? '?' . http_build_query($query) : '');
        };

        $items = [];
        if ($simulation) {
            foreach (require __DIR__ . '/../../resources/portal/articles.php' as $slug => $entry) {
                [$type, $category, $title, $body] = $entry;
                if ($active === 'berita' || $active === $type || ($active === 'home' && (($type === 'organisasi' && $isLeadership($slug)) || ($type === 'berita' && $category === 'Berita utama')))) {
                    $items[] = [
                        'id' => count($items) + 1,
                        'id_pengenal' => $slug,
                        'jenis_halaman' => $type,
                        'kelompok' => $category,
                        'judul' => $title,
                        'uraian_html' => $body,
                        'gambar' => $entry[4] ?? '',
                    ];
                }
            }
        } else {
            try {
                $model = new BeritaModel();
                $items = $model->getAll(null, $active === 'berita' ? null : $active);
                if ($active === 'home') {
                    foreach ($model->getAll(null, 'berita') as $headline) {
                        if ($headline['kelompok'] === 'Berita utama') {
                            $items[] = $headline;
                            break;
                        }
                    }
                    foreach ($model->getAll(null, 'organisasi') as $profile) {
                        if ($isLeadership($profile['id_pengenal'])) {
                            $items[] = $profile;
                        }
                    }
                }
            } catch (DatabaseError $error) {
                if ($active !== 'home') {
                    throw $error;
                }
            }
        }

        if ($active === 'berita') {
            usort($items, static fn (array $left, array $right): int =>
                (int)($right['jenis_halaman'] === 'berita' && $right['kelompok'] === 'Berita utama')
                <=> (int)($left['jenis_halaman'] === 'berita' && $left['kelompok'] === 'Berita utama')
            );
        }
        if ($active === 'pelayanan') {
            usort($items, static fn (array $left, array $right): int =>
                ($serviceOrder[$left['id_pengenal']] ?? PHP_INT_MAX)
                <=> ($serviceOrder[$right['id_pengenal']] ?? PHP_INT_MAX)
            );
        }

        $escape = static fn ($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $sanitizer = new DynamicSanitizer(null);
        $articleKey = $_GET['artikel'] ?? null;
        $article = null;
        $leadership = [];
        $regionalProfile = null;
        $headline = null;
        foreach ($items as &$item) {
            // Use a stable database ID for legacy rows without a unique slug.
            $key = $simulation ? $item['id_pengenal'] : (string)$item['id'];
            $item['portrait'] = $item['jenis_halaman'] === 'organisasi' && isset($leadershipOrder[$item['id_pengenal']]);
            $item['regional_profile'] = $item['jenis_halaman'] === 'organisasi' && $item['id_pengenal'] === $regionalProfileSlug;
            $page = $active === 'home' ? $item['jenis_halaman'] : $active;
            $item['url'] = $pageUrl($page, ['artikel' => $key]);
            $item['jenis_halaman'] = isset($pages[$item['jenis_halaman']]) ? $item['jenis_halaman'] : 'berita';
            $item['body'] = $sanitizer->sanitizeValue($item['uraian_html'] ?? '', ['html' => true]);
            preg_match('/<p\b[^>]*>(.*?)<\/p>/is', $item['body'], $lead);
            $plain = html_entity_decode(strip_tags(str_replace('<', ' <', $item['body'])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $leadText = isset($lead[1]) ? html_entity_decode(strip_tags($lead[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') : $plain;
            $item['search'] = trim(preg_replace('/\s+/u', ' ', $item['judul'] . ' ' . $item['kelompok'] . ' ' . $plain));
            $item['summary'] = mb_strimwidth(trim(preg_replace('/\s+/u', ' ', $leadText)), 0, 198, '…');

            $image = trim((string)($item['gambar'] ?? ''));
            if ($image === '') {
                preg_match('/<img\b[^>]*\bsrc="([^"]+)"/i', $item['body'], $cover);
                $image = html_entity_decode($cover[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            $item['cover'] = $image !== '/assets/img/umum/bg.jpeg'
                && preg_match('~^(?:/(?!/)|https?://)~i', $image) ? $image : $sectionCovers[$item['jenis_halaman']];
            $item['featured_service'] = $item['jenis_halaman'] === 'pelayanan' && isset($serviceOrder[$item['id_pengenal']]);

            if ($item['portrait']) {
                $item['cover_caption'] = $item['id_pengenal'] === 'kepala-dinas-pupr-pasangkayu'
                    ? 'Visualisasi profil dengan perlengkapan K3 berdasarkan foto Dinas PUPR.'
                    : 'Foto dari Pemerintah Kabupaten Pasangkayu.';
                if ($active === 'home') {
                    $leadership[$leadershipOrder[$item['id_pengenal']]] ??= $item;
                }
            } elseif ($item['regional_profile']) {
                $item['cover_caption'] = 'Bupati Yaumil Ambo Djiwa dan Wakil Bupati Herny Agus di rapat paripurna DPRD, 10 Maret 2025. Foto Humas Pemkab Pasangkayu.';
                if ($active === 'home' || $active === 'organisasi') {
                    $regionalProfile ??= $item;
                }
            } elseif (str_ends_with($item['cover'], '-konsep.webp')) {
                $item['cover_caption'] = 'Visualisasi lingkungan berdasarkan foto rujukan.';
            } elseif (str_starts_with($item['cover'], '/assets/img/portal/')) {
                $item['cover_caption'] = 'Ilustrasi editorial.';
            }

            if ($active === 'home' && $item['jenis_halaman'] === 'berita' && $item['kelompok'] === 'Berita utama') {
                $headline ??= $item;
            }
            if ($page === $active && $key === $articleKey) {
                $article = $item;
            }
        }
        unset($item);
        if ($active === 'home') {
            $items = array_values(array_filter($items, static fn ($item) => $item['jenis_halaman'] === 'home'));
            ksort($leadership);
        }

        if ($articleKey !== null && $article === null) {
            http_response_code(404);
        }

        $this->view('home/public_content', compact(
            'active', 'pages', 'simulation', 'pageUrl', 'items', 'article', 'articleKey', 'escape', 'sectionCovers', 'leadership', 'headline', 'regionalProfile'
        ), 'public');
    }
}
