<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use PDO;

final class AppearanceController
{
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'];

    private function guard(): void { require_admin(); }
    private function page(string $view): string { $this->guard(); return view('layouts.admin', ['content' => view('admin.appearance-' . $view, ['settings' => $this->settings()])]); }
    private function settings(): array { return db()->query('SELECT setting_key,setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR); }
    private function save(array $values): void { $stmt = db()->prepare("INSERT INTO settings(setting_group,setting_key,setting_value,is_public) VALUES ('appearance',?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),is_public=1"); foreach ($values as $key => $value) $stmt->execute([$key, (string) $value]); }

    private function upload(Request $r, string $field, string $collection, string $settingKey, int $maxBytes = 5242880): ?string
    {
        $file = $r->files[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size'] > $maxBytes) throw new \RuntimeException('File gambar gagal atau terlalu besar.');
        $info = @getimagesize($file['tmp_name']);
        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] > 5000 || $info[1] > 5000 || ($info[0] * $info[1]) > 20000000) throw new \RuntimeException('Dimensi gambar tidak valid.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::IMAGE_TYPES[$mime]) || ($mime === 'image/svg+xml')) throw new \RuntimeException('Format gambar tidak didukung.');
        $directory = BASE_PATH . '/storage/uploads/' . $collection;
        if (!is_dir($directory) || !is_writable($directory)) throw new \RuntimeException('Folder upload tidak dapat ditulis.');
        $name = bin2hex(random_bytes(16)) . '.' . self::IMAGE_TYPES[$mime];
        $target = $directory . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) throw new \RuntimeException('Gagal menyimpan gambar.');
        $old = setting($settingKey);
        if ($old && is_file($directory . '/' . basename($old))) @unlink($directory . '/' . basename($old));
        return $name;
    }

    public function branding(): string { return $this->page('branding'); }
    public function saveBranding(Request $r): Response { $this->guard(); verify_csrf(); $values=['branding_logo_alt'=>mb_substr(trim((string)$r->get('branding_logo_alt')),0,120),'branding_show_name'=>$r->get('branding_show_name')?'1':'0','branding_logo_width'=>max(80,min(320,(int)$r->get('branding_logo_width',180))),'branding_meta_title'=>mb_substr(trim((string)$r->get('branding_meta_title')),0,160),'branding_meta_description'=>mb_substr(trim((string)$r->get('branding_meta_description')),0,300)]; foreach([['branding_logo','branding_logo',2097152],['branding_logo_light','branding_logo_light',2097152],['branding_favicon','branding_favicon',524288]] as [$field,$key,$max]) if($file=$this->upload($r,$field,'branding',$key,$max)) $values[$key]=$file; $this->save($values); flash('success','Identitas merek tersimpan.'); return redirect('/admin/appearance/branding'); }
    public function homepage(): string { return $this->page('homepage'); }
    public function saveHomepage(Request $r): Response { $this->guard(); verify_csrf(); $allowed=['hero','banner','categories','products','benefits','cta'];$order=array_values(array_unique(array_intersect(array_map('trim',explode(',',(string)$r->get('homepage_section_order'))),$allowed)));foreach($allowed as $section)if(!in_array($section,$order,true))$order[]=$section;$keys=['homepage_announcement_text','homepage_announcement_url','homepage_hero_eyebrow','homepage_hero_title','homepage_hero_accent','homepage_hero_description','homepage_hero_cta_label','homepage_hero_cta_url','homepage_banner_eyebrow','homepage_banner_title','homepage_banner_description','homepage_banner_cta_label','homepage_banner_cta_url','homepage_categories_eyebrow','homepage_categories_title','homepage_products_eyebrow','homepage_products_title','homepage_products_cta_label','homepage_benefits_title','homepage_cta_eyebrow','homepage_cta_title','homepage_cta_description','homepage_cta_label','homepage_cta_url'];$values=[];foreach($keys as $key)$values[$key]=mb_substr(trim((string)$r->get($key)),0,str_contains($key,'description')?400:180);foreach(['homepage_announcement_enabled','homepage_banner_enabled','homepage_categories_enabled','homepage_products_enabled','homepage_benefits_enabled','homepage_cta_enabled'] as $key)$values[$key]=$r->get($key)?'1':'0';$values['homepage_section_order']=json_encode($order);$values['homepage_products_count']=max(4,min(16,(int)$r->get('homepage_products_count',8)));$values['homepage_benefits_json']=json_encode(array_values(array_filter($r->get('benefits',[]),fn($x)=>trim((string)($x['title']??''))!=='')),JSON_UNESCAPED_UNICODE);foreach([['homepage_hero_image','homepage_hero_image'],['homepage_hero_mobile','homepage_hero_mobile'],['homepage_banner_image','homepage_banner_image'],['homepage_banner_mobile','homepage_banner_mobile'],['homepage_cta_image','homepage_cta_image']] as [$field,$key])if($file=$this->upload($r,$field,'homepage',$key))$values[$key]=$file;$this->save($values);flash('success','Halaman depan tersimpan.');return redirect('/admin/appearance/homepage'); }
    public function footer(): string { return $this->page('footer'); }
    public function saveFooter(Request $r): Response { $this->guard(); verify_csrf(); $keys=['footer_description','footer_email','footer_hours','footer_copyright','footer_layout','footer_instagram','footer_facebook','footer_tiktok','footer_youtube','footer_privacy_url','footer_terms_url','footer_returns_url'];$values=[];foreach($keys as $key)$values[$key]=mb_substr(trim((string)$r->get($key)),0,400);$values['footer_show_logo']=$r->get('footer_show_logo')?'1':'0';$values['footer_show_powered']=$r->get('footer_show_powered')?'1':'0';$values['footer_links_json']=json_encode(array_values(array_filter($r->get('links',[]),fn($x)=>trim((string)($x['label']??''))!==''&&trim((string)($x['url']??''))!=='')),JSON_UNESCAPED_UNICODE);$this->save($values);flash('success','Footer tersimpan.');return redirect('/admin/appearance/footer'); }
}
