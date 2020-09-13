<?php
session_start();
require('dbconnect.php');

// var_dump($_FILES['image']);
// exit;

// 機能拡張
if (!empty($_FILES['image'])) {
	// 投稿画像ファイルの拡張子チェック
	$fileName = $_FILES['image']['name'];
	if (!empty($fileName)) {
		$ext = substr($fileName, -3);
		if ($ext != 'jpg' && $ext != 'gif') {
			$error['image'] = 'type';
		}
	}
	if (empty($error)) {
		// 画像をアップロードする
		$image = date('YmdHis') . $_FILES['image']['name'];
		move_uploaded_file($_FILES['image']['tmp_name'], 'post_picture/' .$image);

	}
}
// ここまで

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();
	$members = $db->prepare('SELECT * FROM members WHERE id=?');
	$members->execute(array($_SESSION['id']));
	$member = $members->fetch();
} else {
	// ログインしていない
	header('Location: login.php');
	exit();
}
// 投稿を記録する
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
    if($_POST['reply_post_id'] != '') {
		$message = $db->prepare('INSERT INTO posts SET member_id=?, message=?,reply_post_id=?,picture_post=?,created=NOW()');
		$message->execute(array(
			$member['id'],
			$_POST['message'],
			$_POST['reply_post_id'],
			$image  //画像投稿機能拡張に伴い追加
		));
    header('Location: index.php'); exit();
    } else {
      $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?,picture_post=?,created=NOW()');
      $message->execute(array(
        $member['id'],
				$_POST['message'],
				$image  //画像投稿機能拡張に伴い追加
      ));
    }
	}
}

// 投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
	$page = 1;
}
$page = max($page, 1);

// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);

$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();


// 返信の場合
if (isset($_REQUEST['res'])) {
	$response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m,	posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
		$response->execute(array($_REQUEST['res']));
		$table = $response->fetch();
		$message = '@' . $table['name'] . ' ' . $table['message'];
	}

	// htmlspecialcharsのショートカット
	function h($value) {
    return htmlspecialchars($value, ENT_QUOTES);
  }
	// 本文内のURLにリンクを設定します
	function makeLink($value) {
		return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)",'<a href="\1\2">\1\2</a>' , $value);
	}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">
  <div style="text-align: right"><a href="logout.php">ログアウト</a></div>
		<form action="" method="post" enctype="multipart/form-data">
		<dl>
			<dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
		<dd>
		<textarea name="message" cols="50" rows="5"><?php echo h($message, ENT_QUOTES); ?></textarea>
		<input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res'], ENT_QUOTES); ?>" />
		</dd>
<!-- 画像アップロード機能追加 -->
		<dt>写真など</dt>
		<dd><input type="file" name="image" size="35" />
			<?php if ($error['post_image'] == 'type'): ?>
			<p class="error">* 写真などは「.gif」または「.jpg」の画像を指定してください
			</p>
			<?php endif; ?>
			<?php if (!empty($error)): ?>
			<p class="error">* 恐れ入りますが、画像を投稿する場合は、改めて指定してください</p>
			<?php endif; ?>
		</dd>
<!-- ここまで -->
		</dl>
		<div>
		<input type="submit" value="投稿する" />
		</div>
		</form>

		<?php
		foreach ($posts as $post):
		?>

		<div class="msg">
    <!-- ユーザーのアイコン画像 -->
		<?php
				$ext1 = substr($post['picture'], -3);
				if ($ext1 == 'jpg' || $ext1 == 'gif'):
		?>
		  <img src="member_picture/<?php echo h($post['picture'], ENT_QUOTES); ?>" width="48" height="48" alt="<?php echo h($post['name'], ENT_QUOTES); ?>" />		
		<?php
			  endif;
		?>

    <?php
				if ($ext1 != 'jpg' && $ext1 != 'gif'):
		?>
		<img src="member_picture/default.gif" width="48" height="48" alt="<?php echo h($post['name'], ENT_QUOTES); ?>" />		
		<?php
			  endif;
		?>

		<!-- 投稿されている画像 -->
		<?php
    		$ext2 = substr($post['picture_post'], -3);
				if ($ext2 == 'jpg' || $ext2 =='gif'):
		?>
    <img src="post_picture/<?php echo h($post['picture_post']); ?>" width="48" height="48" alt="<?php echo h($post['picture_post'], ENT_QUOTES); ?>" />
		<?php
			  endif;
		?>

		<?php
				if ($ext2 != 'jpg' && $ext2 != 'gif'):
		?>
    <img src="post_picture/No_Image.jpg" width="48" height="48" alt="画像なし" />

		<?php
			  endif;
		?>

		<p><?php echo makeLink(h($post['message'], ENT_QUOTES));?><span class="name">（<?php echo h($post['name'], ENT_QUOTES); ?>）</span>
    [<a href="index.php?res=<?php echo h($post['id'], ENT_QUOTES); ?>">Re</a>]</p>
		<p class="day"><a href="view.php?id=<?php echo h($post['id'], ENT_QUOTES); ?>"><?php echo h($post['created'], ENT_QUOTES); ?></a>

      <?php
				if ($post['reply_post_id'] > 0):
			?>
			<a href="view.php?id=<?php echo h($post['reply_post_id'], ENT_QUOTES); ?>">返信元のメッセージ</a>
			<?php
			  endif;
			?>

				<?php
				if ($_SESSION['id'] == $post['member_id']):
					?>
					[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:#F33;">削除</a>]
						<?php
					endif;
					?>

			</p>
		</div>
		<?php
		endforeach;
		?>

    <ul class="paging">
    <?php
    if ($page > 1) {
    ?>
    <li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
    <?php
    } else {
    ?>
    <li>前のページへ</li>
    <?php
    }
    ?>
    <?php
    if ($page < $maxPage) {
    ?>
    <li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
    <?php
    } else {
    ?>
    <li>次のページへ</li>
    <?php
    }
    ?>
    </ul>
  </div>

</div>
</body>
</html>
