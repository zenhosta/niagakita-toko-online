<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
use Throwable;

final class InstallController
{
    public function index(): string
    {
        $requirements = [
            'PHP 8.2+' => version_compare(PHP_VERSION, '8.2', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'OpenSSL' => extension_loaded('openssl'),
            'Config dapat ditulis' => is_writable(BASE_PATH . '/config'),
            'Storage dapat ditulis' => is_writable(BASE_PATH . '/storage'),
        ];
        return view('installer.index', ['requirements' => $requirements]);
    }

    public function install(Request $request): Response
    {
        verify_csrf();
        $data = $request->all();
        if (!hash_equals('zenhosta', (string) ($data['license_code'] ?? ''))) {
            $_SESSION['license_attempts'] = (int) ($_SESSION['license_attempts'] ?? 0) + 1;
            if ($_SESSION['license_attempts'] >= 5) sleep(2);
            throw new \RuntimeException('Kode lisensi tidak valid.');
        }
        foreach (['db_host','db_port','db_name','db_user','store_name','whatsapp','admin_name','admin_email','admin_password'] as $field) {
            if (trim((string)($data[$field] ?? '')) === '') throw new \RuntimeException('Semua kolom wajib harus diisi.');
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $data['db_name'])) throw new \RuntimeException('Nama database hanya boleh huruf, angka, dan underscore.');
        if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Email admin tidak valid.');
        if (strlen($data['admin_password']) < 8 || $data['admin_password'] !== ($data['admin_password_confirmation'] ?? '')) throw new \RuntimeException('Password minimal 8 karakter dan konfirmasi harus sama.');

        $config = ['host' => trim($data['db_host']), 'port' => (int)$data['db_port'], 'database' => $data['db_name'], 'username' => trim($data['db_user']), 'password' => (string)($data['db_password'] ?? ''), 'charset' => 'utf8mb4'];
        try {
            $server = Database::connection($config, true);
            if (!empty($data['create_database'])) $server->exec('CREATE DATABASE IF NOT EXISTS `' . $config['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo = Database::connection($config);
            $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
            $pdo->exec($schema);
            $pdo->beginTransaction();
            $pdo->exec("INSERT IGNORE INTO roles(name,permissions_json) VALUES ('admin','[\"*\"]'),('staff','[\"catalog\",\"inventory\",\"orders\"]')");
            $roleId = $pdo->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO users(role_id,name,email,password_hash) VALUES (?,?,?,?)');
            $stmt->execute([$roleId, trim($data['admin_name']), strtolower(trim($data['admin_email'])), password_hash($data['admin_password'], PASSWORD_DEFAULT)]);
            $settings = ['store_name'=>$data['store_name'],'store_tagline'=>$data['store_tagline'] ?? 'Belanja mudah, pilihan lengkap.','store_whatsapp'=>preg_replace('/\D+/', '', $data['whatsapp']),'store_address'=>$data['store_address'] ?? '','shipping_mode'=>$data['shipping_mode'] ?? 'manual','theme_primary'=>'#173f35','theme_accent'=>'#ed7b45','theme_background'=>'#f5f0e7','currency'=>'IDR','homepage_section_order'=>'["hero","banner","categories","products","benefits","cta"]','homepage_hero_eyebrow'=>'Etalase untuk keseharian','homepage_hero_title'=>'Temukan barang yang terasa','homepage_hero_accent'=>'tepat.','homepage_hero_description'=>$data['store_tagline'] ?? 'Pilihan produk untuk setiap momen.','homepage_hero_cta_label'=>'Jelajahi koleksi','homepage_hero_cta_url'=>'/produk','footer_description'=>$data['store_tagline'] ?? 'Belanja mudah, pilihan lengkap.','footer_layout'=>'three'];
            $stmt = $pdo->prepare("INSERT INTO settings(setting_group,setting_key,setting_value,is_public) VALUES ('general',?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            foreach ($settings as $key => $value) $stmt->execute([$key, $value]);
            $pdo->exec("INSERT IGNORE INTO themes(name,slug,config_json,is_active) VALUES ('Atelier Natural','atelier-natural','{\"primary\":\"#173f35\",\"accent\":\"#ed7b45\",\"background\":\"#f5f0e7\"}',1),('Malam Editorial','malam-editorial','{\"primary\":\"#151515\",\"accent\":\"#d7b46a\",\"background\":\"#eeeeea\"}',0)");
            $pdo->commit();
            $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            $tmp = BASE_PATH . '/config/database.local.php.tmp';
            if (file_put_contents($tmp, $content, LOCK_EX) === false || !rename($tmp, BASE_PATH . '/config/database.local.php')) throw new \RuntimeException('Gagal menyimpan konfigurasi database.');
            $lock = ['installation_id'=>bin2hex(random_bytes(16)),'installed_at'=>date(DATE_ATOM),'app_version'=>'1.0.0','license_verified'=>true,'license_product'=>'zenhosta-store'];
            file_put_contents(BASE_PATH . '/storage/installed.lock', json_encode($lock, JSON_PRETTY_PRINT), LOCK_EX);
            session_regenerate_id(true);
            flash('success', 'Instalasi selesai. Masuk dengan akun admin Anda.');
            return redirect('/login');
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            error_log('[' . date('c') . '] Installer: ' . $e->getMessage() . PHP_EOL, 3, BASE_PATH . '/storage/logs/install.log');
            throw new \RuntimeException('Instalasi gagal. Periksa konfigurasi database dan log installer.');
        }
    }
}
