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

/* フォームごとにセッションキーを分離（同一ドメインに複数フォームがあっても衝突しにくくする） */
$ns       = 'fb_' . md5(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$keyToken = $ns.'_token';
$keyIssued= $ns.'_issued_at';
$keyData  = $ns.'_data';
$keyErr   = $ns.'_err';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ew($s,$suf){ return substr($s,-strlen($suf)) === $suf; }

/* -------- POST → セッション保存 → GET へリダイレクト（PRG） -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['__labels'])) { header('Location: form.php'); exit; }

    $token = bin2hex(random_bytes(16));
    $_SESSION[$keyToken]  = $token;
    $_SESSION[$keyIssued] = time();
    $_SESSION[$keyData]   = $_POST;

    header('Location: confirm.php');
    exit;
}

/* -------- GET（表示） -------- */
$post = $_SESSION[$keyData] ?? null;
if (!$post || !is_array($post) || empty($post['__labels'])) { header('Location: form.php'); exit; }

$token = $_SESSION[$keyToken] ?? '';
if ($token === '') { header('Location: form.php'); exit; }

$labels = json_decode($post['__labels'] ?? '[]', true);
$done   = [];
$err    = $_SESSION[$keyErr] ?? '';
unset($_SESSION[$keyErr]);
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

<form action="send.php" method="post"><h2>確認画面</h2>
<?php if ($err): ?>
<p style="padding:1rem;border:1px solid #f00;background:#fff0f0;"><?=h($err)?></p>
<?php endif; ?>
<table class="ta1">
<?php foreach ($post as $k => $v):
    if ($k==='__labels' || $k==='__replyto' || $k==='__required' || $k==='__hp' || in_array($k,$done,true)) continue;

    /* 住所まとめ（3 項目とも空なら非表示） */
    if (ew($k,'_zip')){
        $b    = substr($k,0,-4);
        $zip  = $post[$b.'_zip']  ?? '';
        $pref = $post[$b.'_pref'] ?? '';
        $addr = $post[$b.'_addr'] ?? '';

        if (trim($zip.$pref.$addr) === ''){
            $done = array_merge($done,["{$b}_zip","{$b}_pref","{$b}_addr"]);
            continue;
        }

        echo '<tr><th>'.h($labels[$k]??'住所').'</th><td>';
        echo h($zip).'<br>';
        echo h($pref.$addr).'</td></tr>';
        $done = array_merge($done,["{$b}_zip","{$b}_pref","{$b}_addr"]);
        continue;
    }

    /* 年月日まとめ（3 項目とも空なら非表示） */
    if (ew($k,'_y')){
        $b = substr($k,0,-2);
        $y = $post[$b.'_y'] ?? '';
        $m = $post[$b.'_m'] ?? '';
        $d = $post[$b.'_d'] ?? '';

        if (trim($y.$m.$d) === ''){
            $done = array_merge($done,["{$b}_y","{$b}_m","{$b}_d"]);
            continue;
        }

        $heading = preg_replace('/\s*[年月日]\z/u','',$labels[$k]??'');
        echo '<tr><th>'.h($heading).'</th><td>';
        echo h("{$y}年{$m}月{$d}日").'</td></tr>';
        $done = array_merge($done,["{$b}_y","{$b}_m","{$b}_d"]); continue;
    }

    /* 月日まとめ（2 項目とも空なら非表示） */
    if (ew($k,'_d_m')){
        $b = substr($k,0,-4);
        $m = $post[$b.'_d_m'] ?? '';
        $d = $post[$b.'_d_d'] ?? '';

        if (trim($m.$d) === ''){
            $done = array_merge($done,["{$b}_d_m","{$b}_d_d"]);
            continue;
        }

        $heading = preg_replace('/\s*[年月日]\z/u','',$labels[$k]??'');
        echo '<tr><th>'.h($heading).'</th><td>';
        echo h("{$m}月{$d}日").'</td></tr>';
        $done = array_merge($done,["{$b}_d_m","{$b}_d_d"]); continue;
    }

    /* 通常（空なら非表示） */
    $vals = is_array($v) ? array_filter($v,'strlen') : [trim($v)];
    if (count($vals) === 0 || $vals[0] === '') continue;

    echo '<tr><th>'.h($labels[$k]??$k).'</th><td>';
    foreach ($vals as $sv) echo nl2br(h($sv)).'<br>';
    echo '</td></tr>';
endforeach; ?>
</table>

<input type="hidden" name="__labels" value="<?=h($post['__labels'] ?? '')?>">
<input type="hidden" name="__token"  value="<?=h($token)?>">
<?php foreach ($post as $k=>$v):
    if ($k==='__labels') continue;
    if (is_array($v)):
        foreach ($v as $sv)
            echo '<input type="hidden" name="'.$k.'[]" value="'.h($sv).'">';
    else:
        echo '<input type="hidden" name="'.$k.'" value="'.h($v).'">';
    endif;
endforeach; ?>
<p class="c">
    <button type="button" onclick="history.back()">修正する</button>
    <button type="submit">送信する</button>
</p>
</form>


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
