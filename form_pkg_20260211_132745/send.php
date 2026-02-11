<?php
// HTTPS のときだけ Secure を付与（http でも動かせるように）
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure','1');
}
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
ini_set('session.use_strict_mode','1');

session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

/* フォームごとにセッションキーを分離 */
$ns       = 'fb_' . md5(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$keyToken = $ns.'_token';
$keyIssued= $ns.'_issued_at';
$keyData  = $ns.'_data';
$keyErr   = $ns.'_err';
$keyDone  = $ns.'_done';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ew($s,$suf){ return substr($s,-strlen($suf)) === $suf; }

/* -------- PRG（送信完了の GET 表示） -------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['done']) && !empty($_SESSION[$keyDone])) {
        unset($_SESSION[$keyDone]);
        ?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>お湯っくり</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="非公式サークルお湯っくりの公式HPです。">
<link rel="stylesheet" href="css/style.css">
</head>

<body>

<div id="container">

<header>
<h1 id="logo"><a href="index.html">♨️お湯っくり</a></h1>
	<!--開閉メニュー-->
	<div id="menubar">
	<nav>
	<ul>
	<li><a href="company.html">お湯っくりについて</a></li>
	<li><a href="service.html">活動報告</a></li>
	<li><a href="staff.html">オリジナルグッズ</a></li>
	<li><a href="recruit.html">採用情報</a></li>
	<li class="btn"><a href="contact.html">ご予約・お問い合わせ</a></li>
	</ul>
	</nav>
	</div>
</header>

<main>

<section>

<h2>お問い合わせ</h2>

<h2>送信完了</h2>
<p>送信を受け付けました。</p>


<table class="ta1">
<caption>お問い合わせフォーム</caption>
<tr>
<th>お名前※</th>
<td><input type="text" name="お名前" size="30" class="ws"></td>
</tr>
<tr>
<th>メールアドレス※</th>
<td><input type="text" name="メールアドレス" size="30" class="ws"></td>
</tr>
<tr>
<th>お問い合わせ詳細※</th>
<td><textarea name="お問い合わせ詳細" cols="30" rows="10" class="wl"></textarea></td>
</tr>
</table>

<p class="c">
<input type="submit" class="btn" value="内容を確認する">
</p>

</section>

</main>

<footer>
<ul class="icons">
<li><a href="#"><i class="fa-brands fa-x-twitter"></i></a></li>
<li><a href="#"><i class="fab fa-line"></i></a></li>
<li><a href="#"><i class="fab fa-youtube"></i></a></li>
<li><a href="#"><i class="fab fa-instagram"></i></a></li>
</ul>
<small>Copyright© お湯っくり All Rights Reserved.</small>
</footer>

<!--以下の行はテンプレートの著作。削除しないで下さい。-->
<span class="pr"><a href="https://template-party.com/" target="_blank">《Web Design:Template-Party》</a></span>

</div>
<!--/#container-->

<!--ページの上部へ戻るボタン-->
<div class="pagetop"><a href="#"><i class="fas fa-angle-double-up"></i></a></div>

<!--開閉ボタン（ハンバーガーアイコン）-->
<div id="menubar_hdr">
<span></span><span></span><span></span>
</div>

<!--jQueryの読み込み-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!--パララックス（inview）-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/protonet-jquery.inview/1.1.2/jquery.inview.min.js"></script>
<script src="js/jquery.inview_set.js"></script>

<!--このテンプレート専用のスクリプト-->
<script src="js/main.js"></script>

</body>
</html>

<?php
        exit;
    }
    header('Location: form.php');
    exit;
}

/* ---- 0. 前提データ（confirm.php で保存されたもの） ---- */
$post = $_SESSION[$keyData] ?? null;
if (!$post || !is_array($post) || empty($post['__labels'])) {
    header('Location: form.php');
    exit;
}

/* ---- 1. ワンタイムトークン検証 ---- */
if (empty($_POST['__token'])
 || empty($_SESSION[$keyToken])
 || !hash_equals($_SESSION[$keyToken], $_POST['__token'])) {
    $_SESSION[$keyErr] = '不正な送信です。ページを戻ってやり直してください。';
    header('Location: confirm.php');
    exit;
}

/* ---- 1a. ハニーポット ---- */
if (!empty($_POST['__hp'])) {
    $_SESSION[$keyErr] = '送信に失敗しました。しばらくしてからお試しください。';
    header('Location: confirm.php');
    exit;
}

/* ---- 1b. タイムゲート（最短1秒） ---- */
$minWait  = 1;  // 0で無効化可
$issuedAt = (int)($_SESSION[$keyIssued] ?? 0);
if ($issuedAt > 0 && (time() - $issuedAt) < $minWait) {
    $_SESSION[$keyErr] = '送信が速すぎます。数秒待ってからもう一度お試しください。';
    header('Location: confirm.php');
    exit;
}

/* ---- 1c. 同一オリジン（Origin/Referer）緩めチェック ---- */
$okOrigin = true;
$host = $_SERVER['HTTP_HOST'] ?? '';
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $o = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    $okOrigin = ($o === $host);
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $r = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $okOrigin = ($r === $host);
}
if (!$okOrigin) {
    $_SESSION[$keyErr] = '送信に失敗しました。ページを戻ってお試しください。';
    header('Location: confirm.php');
    exit;
}

/* ---- 1d. POST総量・項目数の上限 ---- */
$MAX_TOTAL  = 16384; // 16KB 相当（総バイト数）
$MAX_FIELDS = 200;   // フィールド総数（配列要素も含む）

$total = 0;
$count = 0;
foreach ($_POST as $k => $v) {
    if (in_array($k, ['__labels','__replyto','__required','__token','__hp'], true)) continue;
    $vals = is_array($v) ? $v : [$v];
    foreach ($vals as $sv) {
        $count++;
        $total += strlen((string)$sv);
        if ($count > $MAX_FIELDS || $total > $MAX_TOTAL) {
            $_SESSION[$keyErr] = '入力が大きすぎます。内容を短くしてお試しください。';
            header('Location: confirm.php');
            exit;
        }
    }
}

/* ---- 1e. サーバー側バリデーション（長さ・制御文字） ---- */
$MAX_LEN = 2000;
foreach ($_POST as $k => $v) {
    if (in_array($k, ['__labels','__replyto','__token'], true)) continue;
    $vals = is_array($v) ? $v : [$v];

    foreach ($vals as $sv) {
        if (mb_strlen($sv) > $MAX_LEN) {
            $_SESSION[$keyErr] = '入力が長すぎます（最大 '.$MAX_LEN.' 文字）。';
            header('Location: confirm.php');
            exit;
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $sv)) {
            $_SESSION[$keyErr] = '不正な文字が含まれています。';
            header('Location: confirm.php');
            exit;
        }
    }
}

/* ========= ここだけ編集してください ========= */
$adminMail        = 'sueoka20000623@gmail.com';            // 管理者が受け取る宛先（To）
$replyToMail      = 'sueoka20000623@gmail.com';                  // 自動返信メールの返信先（Reply-To）。通常は管理者アドレスでOK
$adminSubject     = '【サイト問合せ】内容通知';    // 管理者宛 件名
$userSubject      = 'お問い合わせありがとうございます'; // 自動返信 件名
$userHeaderLead   = "以下の内容で受け付けました。\n\n"; // 自動返信 文頭
$signature        = <<< 'SIG'
──────────────────────
お湯っくり
https://example.com/
──────────────────────
SIG;
$enableUserReply  = true;                          // true: 自動返信を送信 / false: 送信しない
/* ============================================ */

/* ---- 1f. 必須項目未入力チェック（分割項目対応） ---- */
$requiredKeys = array_filter(explode(',', $_POST['__required'] ?? ''));
foreach ($requiredKeys as $rk){
    $val = $_POST[$rk] ?? null;

    if ($val !== null) {
        $isEmpty = is_array($val)
            ? count(array_filter($val,'strlen')) === 0
            : trim((string)$val) === '';
        if ($isEmpty){
            $_SESSION[$keyErr] = '必須項目が未入力です。';
            header('Location: confirm.php');
            exit;
        }
        continue;
    }

    /* 古い生成フォーム対策：__required に「ベース名」が入っていて、実体は分割フィールドのケース */
    $addrKeys = [$rk.'_zip', $rk.'_pref', $rk.'_addr'];
    if (isset($_POST[$addrKeys[0]]) || isset($_POST[$addrKeys[1]]) || isset($_POST[$addrKeys[2]])) {
        if (trim((string)($_POST[$addrKeys[0]] ?? '')) === ''
         || trim((string)($_POST[$addrKeys[1]] ?? '')) === ''
         || trim((string)($_POST[$addrKeys[2]] ?? '')) === '') {
            $_SESSION[$keyErr] = '必須項目が未入力です。';
            header('Location: confirm.php');
            exit;
        }
        continue;
    }

    $ymdKeys = [$rk.'_y', $rk.'_m', $rk.'_d'];
    if (isset($_POST[$ymdKeys[0]]) || isset($_POST[$ymdKeys[1]]) || isset($_POST[$ymdKeys[2]])) {
        if (trim((string)($_POST[$ymdKeys[0]] ?? '')) === ''
         || trim((string)($_POST[$ymdKeys[1]] ?? '')) === ''
         || trim((string)($_POST[$ymdKeys[2]] ?? '')) === '') {
            $_SESSION[$keyErr] = '必須項目が未入力です。';
            header('Location: confirm.php');
            exit;
        }
        continue;
    }

    $mdKeys = [$rk.'_d_m', $rk.'_d_d'];
    if (isset($_POST[$mdKeys[0]]) || isset($_POST[$mdKeys[1]])) {
        if (trim((string)($_POST[$mdKeys[0]] ?? '')) === ''
         || trim((string)($_POST[$mdKeys[1]] ?? '')) === '') {
            $_SESSION[$keyErr] = '必須項目が未入力です。';
            header('Location: confirm.php');
            exit;
        }
        continue;
    }

    /* ここまで来る＝キーそのものが存在しない（想定外） */
    $_SESSION[$keyErr] = '必須項目が未入力です。';
    header('Location: confirm.php');
    exit;
}

/* ---- 1g. メールアドレス形式チェック ---- */
$emailKeys = array_filter(explode(',', $_POST['__replyto'] ?? ''));
foreach ($emailKeys as $ek){
    $v = $_POST[$ek] ?? '';
    if ($v !== '' && !filter_var(is_array($v)?($v[0]??''):$v, FILTER_VALIDATE_EMAIL)){
        $_SESSION[$keyErr] = 'メールアドレスの形式が正しくありません。';
        header('Location: confirm.php');
        exit;
    }
}

/* ---- 2. IP 連投 30 秒制限 ---- */
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ips = array_filter(array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
    $ip  = $ips[0];
} else {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
}
$ip = preg_replace('/[^0-9a-fA-F:.]/', '_', $ip);

$stampFile = sys_get_temp_dir().'/form_last_'.preg_replace('/[^0-9a-fA-F:]/','_',$ip);
$now = time();
if (file_exists($stampFile)) {
    $elapsed = $now - (int)file_get_contents($stampFile);
    if ($elapsed < 30) {
        $_SESSION[$keyErr] = '30秒以内の連続送信はできません。少し時間をあけて送信して下さい。';
        header('Location: confirm.php');
        exit;
    }
}
file_put_contents($stampFile, (string)$now);

/* ---- 2b. 時間・日次レート制限 ---- */
$MAX_PER_HOUR = 20;
$MAX_PER_DAY  = 100;

$hourFile = sys_get_temp_dir().'/form_count_'.$ip.'_h_'.date('YmdH');
$dayFile  = sys_get_temp_dir().'/form_count_'.$ip.'_d_'.date('Ymd');

$hour = is_file($hourFile) ? (int)@file_get_contents($hourFile) : 0;
$day  = is_file($dayFile)  ? (int)@file_get_contents($dayFile)  : 0;

if ($hour >= $MAX_PER_HOUR || $day >= $MAX_PER_DAY) {
    $_SESSION[$keyErr] = '送信上限に達しました。しばらく時間を置いてお試しください。';
    header('Location: confirm.php');
    exit;
}
@file_put_contents($hourFile, (string)($hour+1), LOCK_EX);
@file_put_contents($dayFile,  (string)($day+1),  LOCK_EX);

/* ---- 3. 送信処理 ---- */
$labels = json_decode($_POST['__labels'] ?? '[]', true);
$done   = [];
$lines  = [];

foreach ($_POST as $k => $v){
    if (in_array($k, ['__labels','__replyto','__required','__token','__hp'], true) || in_array($k, $done, true)) continue;

    /* 住所（3 項目とも空なら非表示） */
    if (ew($k, '_zip')){
        $b    = substr($k, 0, -4);
        $zip  = $_POST[$b.'_zip']  ?? '';
        $pref = $_POST[$b.'_pref'] ?? '';
        $addr = $_POST[$b.'_addr'] ?? '';

        if (trim($zip.$pref.$addr) === ''){
            $done = array_merge($done, ["{$b}_zip","{$b}_pref","{$b}_addr"]);
            continue;
        }

        $lines[] = "[住所] {$zip} / {$pref}{$addr}";
        $done = array_merge($done, ["{$b}_zip","{$b}_pref","{$b}_addr"]);
        continue;
    }

    /* 年月日（3 項目とも空なら非表示） */
    if (ew($k, '_y')){
        $b = substr($k, 0, -2);
        $y = $_POST[$b.'_y'] ?? '';
        $m = $_POST[$b.'_m'] ?? '';
        $d = $_POST[$b.'_d'] ?? '';

        if (trim($y.$m.$d) === ''){
            $done = array_merge($done, ["{$b}_y","{$b}_m","{$b}_d"]);
            continue;
        }

        $lines[] = "[日付] {$y}年{$m}月{$d}日";
        $done = array_merge($done, ["{$b}_y","{$b}_m","{$b}_d"]);
        continue;
    }

    /* 月日（2 項目とも空なら非表示） */
    if (ew($k, '_d_m')){
        $b = substr($k, 0, -4);
        $m = $_POST[$b.'_d_m'] ?? '';
        $d = $_POST[$b.'_d_d'] ?? '';

        if (trim($m.$d) === ''){
            $done = array_merge($done, ["{$b}_d_m","{$b}_d_d"]);
            continue;
        }

        $lines[] = "[日付] {$m}月{$d}日";
        $done = array_merge($done, ["{$b}_d_m","{$b}_d_d"]);
        continue;
    }

    /* 通常（空なら非表示） */
    $vals = is_array($v) ? array_filter($v,'strlen') : [trim($v)];
    if (count($vals) === 0 || $vals[0] === '') continue;

    $lines[] = '[' . ($labels[$k] ?? $k) . '] ' . (is_array($v) ? implode(', ', $vals) : $vals[0]);
}

$bodyAdmin = implode("\n", $lines) . "\n";

// Reply-To（ユーザーが入力したメールアドレスがあれば採用）
$userEmail = '';
$emailKeys = array_filter(explode(',', $_POST['__replyto'] ?? ''));
foreach ($emailKeys as $ek){
    $v = $_POST[$ek] ?? '';
    $candidate = is_array($v) ? ($v[0] ?? '') : $v;
    if ($candidate && filter_var($candidate, FILTER_VALIDATE_EMAIL)) { $userEmail = $candidate; break; }
}

// ヘッダ改ざん対策（念のため）
$userEmail   = preg_replace('/[\r\n]+/', '', $userEmail);
$adminMail   = preg_replace('/[\r\n]+/', '', $adminMail);
$replyToMail = preg_replace('/[\r\n]+/', '', $replyToMail);

$fromAddr = $host ?: 'example.com';
$fromAddr = 'no-reply@' . preg_replace('/[^0-9a-zA-Z.-]/','', $fromAddr);

mb_language('Japanese');
mb_internal_encoding('UTF-8');

$commonLines = [
    "From: {$fromAddr}",
    "MIME-Version: 1.0",
    "Content-Type: text/plain; charset=UTF-8",
    "Content-Transfer-Encoding: 8bit",
];
$commonHead = implode("\r\n", $commonLines) . "\r\n";

$adminHeaders = $commonHead;
if (!empty($userEmail)) {
    $adminHeaders .= "Reply-To: {$userEmail}\r\n";
}
mb_send_mail($adminMail, $adminSubject, $bodyAdmin, $adminHeaders, "-f{$fromAddr}");

if ($enableUserReply && $userEmail){
    $bodyUser = $userHeaderLead . implode("\n", $lines) . "\n\n" . $signature;
    $userHeaders = $commonHead;
    if (!empty($replyToMail) && filter_var($replyToMail, FILTER_VALIDATE_EMAIL)) {
        $userHeaders .= "Reply-To: {$replyToMail}\r\n";
    }
    mb_send_mail($userEmail, $userSubject, $bodyUser, $userHeaders, "-f{$fromAddr}");
}

/* 成功：トークン破棄・入力データ破棄 → 完了ページへ */
unset($_SESSION[$keyToken], $_SESSION[$keyIssued], $_SESSION[$keyData]);
$_SESSION[$keyDone] = 1;
header('Location: send.php?done=1');
exit;
?>