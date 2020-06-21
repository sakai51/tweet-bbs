<?php
session_start();
require('dbconnect.php');
if (empty($_REQUEST['id'])) {
	header('Location: index.php'); exit();
}
// 投稿を取得する
$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
$posts->execute(array($_REQUEST['id']));
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
			<p>&laquo;<a href="index.php">一覧にもどる</a></p>

			<?php
			if ($post = $posts->fetch()):
				?>
				<div class="msg">
					<img src="member_picture/<?php echo htmlspecialchars($post['picture'], ENT_QUOTES); ?>" width="48" height="48" alt="<?php echo 	 htmlspecialchars($post['name'], ENT_QUOTES); ?>" />
					<p><?php echo htmlspecialchars($post['message'], ENT_QUOTES);
					?><span class="name">（<?php echo htmlspecialchars($post['name'], ENT_QUOTES); ?>）</span></p>
					<p class="day"><?php echo htmlspecialchars($post['created'], ENT_QUOTES); ?></p>
				</div>
				<?php
			else:
				?>
				<p>その投稿は削除されたか、URLが間違えています</p>
				<?php
			endif;
			?>
		</div>
	</div>
</body>
</html>
