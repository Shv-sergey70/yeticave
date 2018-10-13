<?php
declare(strict_types=1);
require_once('functions.php');
require_once('const.php');
$link = require_once('db_conn.php');
session_start();
$USER = isset($_SESSION['USER'])?$_SESSION['USER']:NULL;

//Запрос на получение пунктов меню
$menu_items_query = 'SELECT * FROM categories';
$menu_items = get_DB_query_res($menu_items_query, $link, true);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
	$search_query = trim($_GET['search']);
	if (empty($search_query)) {
		$search_error = 'Строка поиска не должна быть пустой';
		$page_content = include_template('search.php', 
	  [
	    'menu_items' => $menu_items,
	    'error' => $search_error
	  ]);
	} else {
		
		$safe_search_query = mysqli_real_escape_string($link, $search_query);
		$items_in_page = 9; //Количество элементов на страницу
		$cur_page = intval($_GET['page'] ?? 1); //Определение текущей страницы

		//Запрос в БД по поисковой фразе, для получения количества лотов
		$searching_cnt_query = "SELECT
														COUNT(*) as CNT
														FROM lots
														WHERE MATCH(lots.name, lots.description)
														AGAINST('$safe_search_query.*' IN BOOLEAN MODE)
														AND lots.date_end > CURDATE()";
		$items_count = (int)get_DB_query_res($searching_cnt_query, $link, false)['CNT']; //Количество элементов

		$pages_count = (int)ceil($items_count/$items_in_page); //Считаем количество страниц
		$offset = ($cur_page - 1) * $items_in_page; //Смещение для запроса к БД
		$pages = range(1, $pages_count); //Массив страниц для пагинации
		if ($cur_page > 1) {
			$prev_page = $cur_page - 1;
		} elseif ($cur_page === 1) {
			$prev_page = $cur_page;
		}
		if ($cur_page < $pages_count) {
			$next_page = $cur_page + 1;
		} elseif ($cur_page === $pages_count) {
			$next_page = $cur_page;
		}

		//Запрос в БД по поисковой фразе
		$searching_query = "SELECT 
												lots.id as ID,
												lots.name as NAME,
												lots.description as DESCRIPTION,
												lots.image_url as IMAGE_URL,
					              lots.date_end as FINISH_DATE,
					              lots.bet_step as PRICE_STEP,
					              lots.author_id as AUTHOR_ID,
					              categories.name as CATEGORY_NAME,
					              COUNT(bets.lot_id) as BETS_COUNT
												FROM lots
												JOIN categories
												ON lots.adv_category_id = categories.id
												LEFT JOIN bets
              					ON bets.lot_id = lots.id
												WHERE MATCH(lots.name, lots.description)
												AGAINST('$safe_search_query.*' IN BOOLEAN MODE) 
												AND lots.date_end > CURDATE()
												GROUP BY bets.lot_id, lots.id
												ORDER BY lots.date_create ASC
												LIMIT $items_in_page
												OFFSET $offset";
		$search_result = get_DB_query_res($searching_query, $link, true);

		foreach ($search_result as $key => $value) {
			if (strtotime($value['FINISH_DATE']) < strtotime('+1 day')) {
				$search_result[$key]['IS_LESS_THAN_24_HOUR'] = true;
			} else {
				$search_result[$key]['IS_LESS_THAN_24_HOUR'] = false;
			}
		}

		$page_content = include_template('search.php', 
	  [
	    'menu_items' => $menu_items,
	    'search_result' => $search_result,
	    'search_query' => $search_query,
	    'pages_count' => $pages_count,
	    'cur_page' => $cur_page,
	    'pages' => $pages,
	    'prev_page' => $prev_page ?? NULL,
	    'next_page' => $next_page ?? NULL
	  ]);
	}
} else {
	$page_content = include_template('search.php', 
  [
    'menu_items' => $menu_items,
    'error' => 'Введите Ваш запрос в поисковую строку'
  ]);
}


$layout_content = include_template('layout.php', 
  [
    'content' => $page_content, 
    'menu_items' => $menu_items, 
    'title' => 'Yeticave', 
    'USER'=> $USER
  ]);
print($layout_content);