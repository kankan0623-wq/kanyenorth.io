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
<form action="confirm.php" method="post" id="genForm"><h2>お問い合わせフォーム</h2><table class="ta1">
<tr><th>お名前<span class=\"req\">*</span></th><td><input type="text" name="f_698c054154f29" class="wl" required /></td></tr>
<tr><th>メールアドレス<span class=\"req\">*</span></th><td><input type="text" name="f_698c054154f2f" class="wl" required /></td></tr>
<tr><th>お問い合わせ詳細<span class=\"req\">*</span></th><td><input type="text" name="f_698c054154f32" class="wl" required /></td></tr>
</table><input type="hidden" name="__labels" value='{"f_698c054154f29":"お名前","f_698c054154f2f":"メールアドレス","f_698c054154f32":"お問い合わせ詳細"}'>
<input type="hidden" name="__required" value="f_698c054154f29,f_698c054154f2f,f_698c054154f32">
<input type="hidden" name="__replyto" value="f_698c054154f2f">
<div class="hp" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true"><label>Website<input type="text" name="__hp" autocomplete="off" tabindex="-1"></label></div>
<p class="c"><button type="submit">確認画面へ</button></p></form><script>
/* 郵便番号検索 */
document.addEventListener('click', e=>{
    if(!e.target.matches('.lookup')) return;
    const blk=e.target.closest('.addr-block');
    const zip=blk.querySelector('.zip').value.replace(/\D/g,'');
    if(zip.length!==7){alert('郵便番号を正しく入力してください');return;}
    fetch('https://zipcloud.ibsnet.co.jp/api/search?zipcode='+zip)
      .then(r=>r.json()).then(d=>{
          if(d.status===200&&d.results){
              const r=d.results[0];
              blk.querySelector('.pref').value  = r.address1;
              blk.querySelector('.addr1').value = r.address2+r.address3;
          }else alert('住所が見つかりません');
      }).catch(()=>alert('通信エラーで検索に失敗しました'));
});

/* チェックボックス 1 つ以上必須 */
document.getElementById('genForm').addEventListener('submit',e=>{
    for(const g of e.target.querySelectorAll('.chk-group[data-required="1"]')){
        if(!g.querySelector('input[type=checkbox]:checked')){
            alert('「'+g.dataset.label+'」を1つ以上選択してください');
            g.scrollIntoView({behavior:'smooth', block:'center'});
            e.preventDefault(); return;
        }
    }
});
</script>

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
