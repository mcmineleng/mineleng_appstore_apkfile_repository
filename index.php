<?php
$file = $_GET['apk'] ?? '';

// 基础校验：不能为空
if ($file === '') {
    http_response_code(400);
    exit('Bad Request');
}

// 安全检查：禁止 .. (目录遍历)，禁止 / 开头 (绝对路径)
// 允许 a.apk, meta/a.apk, deep/meta/a.apk 等
if (strpos($file, '..') !== false || strpos($file, '/') === 0) {
    http_response_code(400);
    exit('Bad Request');
}

// 可选：进一步限制只允许安全字符（推荐）
if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $file)) {
    http_response_code(400);
    exit('Bad Request');
}

$listFile = __DIR__ . '/list.json';

// --- 第一步：查 list.json ---
if (file_exists($listFile)) {
    $data = json_decode(file_get_contents($listFile), true);
    if (is_array($data)) {
        foreach ($data as $item) {
            if (isset($item['filename']) && $item['filename'] === $file && !empty($item['url'])) {
                // 命中 → 301 跳转
                header('Location: ' . $item['url'], true, 301);
                exit;
            }
        }
    }
}

// --- 第二步：list.json 没命中，尝试从 ./repo/ 直接读文件 ---
$repoPath = __DIR__ . '/repo/' . $file;

if (file_exists($repoPath) && is_file($repoPath)) {
    // 根据文件扩展名猜测 Content-Type
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($repoPath) ?: 'application/octet-stream';
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($repoPath) ?: 'application/octet-stream';
    } else {
        $mime = 'application/octet-stream';
    }
    
    header('Content-Type: ' . $mime);
    readfile($repoPath);
    exit;
}

// --- 第三步：都没有 → 404 ---
http_response_code(404);
exit('Not Found');