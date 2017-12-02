<?php
/**
 * @var \MrTimofey\LaravelAioImages\ImageModel[] $images
 * @var mixed $func_num
 * @var string $thumb_pipe
 */
?><!DOCTYPE html>
<html>
<head>
	<title>Выберите файл</title>
	<meta charset="utf-8">
	<style>
		body {
			font-family: sans-serif;
		}

		a {
			display: inline-block;
			vertical-align: top;
			margin: 5px;
			cursor: pointer;
		}

		img {
			display: block;
		}
	</style>
	<script>
		function selectImage(path) {
			window.opener.CKEDITOR.tools.callFunction('<?=$func_num?>', path);
			window.close();
		}
	</script>
</head>
<body>
<?php if (\count($images)): ?>
	<div class="image-list">
        <?php foreach ($images as $i): ?>
			<a onclick="selectImage('<?php echo $i->getPath(); ?>')">
				<img src="<?php echo $i->getPath($thumb_pipe) ?>">
			</a>
        <?php endforeach; ?>
	</div>
<?php else: ?>
	<h1 style="text-align:center;opacity:0.5">Нет загруженных картинок</h1>
<?php endif; ?>
<p style="text-align:center">Для загрузки новой картинки закройте это окно и загрузите картинку на вкладке
	"Загрузить"</p>
</body>
</html>