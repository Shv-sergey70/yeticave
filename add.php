<?php
require_once('functions.php');
require_once('const.php');
$link = require_once('db_conn.php');
$user = require_once('user.php');

//Запрос на получение пунктов меню
$menu_items_query = 'SELECT * FROM categories';
$menu_items = get_DB_query_rows($menu_items_query, $link);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$lot = $_POST;

	$required = ['NAME', 'CATEGORY', 'DESCRIPTION', 'START_PRICE', 'PRICE_STEP', 'FINISH_DATE'];
	$dict = ['NAME' => 'Наименование', 'CATEGORY' => 'Категория', 'DESCRIPTION' => 'Описание', 'IMAGE_URL' => 'Изображение', 'START_PRICE' => 'Начальная цена', 'PRICE_STEP' => 'Шаг ставки', 'FINISH_DATE' => 'Дата окончания торгов'];
	$errors = [];
	foreach ($required as $value) {
		if (empty($lot[$value])) {
			$errors[$value] = 'Это поле надо заполнить';
		}
	}
	if (!ctype_digit($lot['START_PRICE']) || intval($lot['START_PRICE']) <= 0) {
		$errors['START_PRICE'] = 'Это поле должно быть целым числом больше нуля';
	}
	if (!ctype_digit($lot['PRICE_STEP']) || intval($lot['PRICE_STEP']) <= 0) {
		$errors['PRICE_STEP'] = 'Это поле должно быть целым числом больше нуля';
	}
	if (!empty($_FILES['IMAGE_URL']['name'])) {
		$tmp_name = $_FILES['IMAGE_URL']['tmp_name'];
		$original_name = $_FILES['IMAGE_URL']['name'];

		$file_type = mime_content_type($tmp_name);

		if ($file_type === 'image/png' || $file_type === 'image/jpeg') {
			move_uploaded_file($tmp_name, 'img/'.$original_name);
			$lot['IMAGE_URL'] = 'img/'.$original_name;
		} else {
			$errors['IMAGE_URL'] = 'Загрузите картинку в формате jpg, jpeg или png';
		}
	} else {
		$errors['IMAGE_URL'] = 'Вы не загрузили картинку';
	}
	if (count($errors)) {
		$page_content = include_template('add.php', 
	  [
	    'menu_items' => $menu_items,
	    'errors' => $errors,
	    'dict' => $dict,
	    'lot' => $lot
	  ]);
	} else {
		//Запрос на добавление нового лота
		//Тк пока не других юзеров, и соответственно их ID - поставил цифру 1
		$lot_add_query = "INSERT INTO lots
											SET
											date_create = NOW(),
											name = '".$lot['NAME']."',
											description = '".$lot['DESCRIPTION']."',
											image_url = '".$lot["IMAGE_URL"]."',
											start_price = '".$lot['START_PRICE']."',
											date_end = '".$lot["FINISH_DATE"]."',
											bet_step = '".$lot['PRICE_STEP']."',
											author_id = 1,
											adv_category_id = ".$lot['CATEGORY'];
		$inserted_lot_id = put_DB_query_row($lot_add_query, $link);
		header('Location: lot.php?ID='.$inserted_lot_id);
	}
}

$page_content = include_template('add.php', 
  [
    'menu_items' => $menu_items
  ]);

$layout_content = include_template('layout.php', 
  [
    'content' => $page_content, 
    'menu_items' => $menu_items, 
    'title' => 'Yeticave', 
    'user'=>$user
  ]);
print($layout_content);