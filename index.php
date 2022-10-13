<?php
function getAtribute($file, $pattern) { // Функция парсинга кинопоиска по заданным патернам
	preg_match_all($pattern, $file, $res);
	return $res[0];
}

// $site = 'https://www.kinopoisk.ru/lists/movies/top250/';
// $file = file_get_contents($site);

$file = file_get_contents('kp2.htm');

$patternName = '#<span class="styles_mainTitle__IFQyZ styles_activeMovieTittle__kJdJj(.+?)</span>#su'; // Паттерн поиска названия фильма
$patternRating = '#<span class="styles_kinopoiskValuePositive__vOb2E styles_kinopoiskValue__9qXjg(.+?)</span>#su'; // Паттерн поиска рейтинга фильма
$patternYear = '#<span class="desktop-list-main-info_secondaryText__M_aus(.+?)</span>#su'; // Паттерн поиска года выхода фильма
$patternDirector = '#<span class="desktop-list-main-info_truncatedText__IMQRP(.+?)</span>#su'; // Паттерн поиска режисёра фильма

$names = getAtribute($file, $patternName); // Названия фильмов
$ratings = getAtribute($file, $patternRating); // Рейтинги фильмов
$years = getAtribute($file, $patternYear); // Года выхода фильмов
$directors = getAtribute($file, $patternDirector); // Использовать только чётные значения    VVV

$directors = array_map('array_shift', array_chunk($directors, 2)); // Берёт каждое чётное значение

for ($i = 0; $i < 20; $i++) { // Костыль по вырезке YEARS и DIRECTORS из всей строки
	$entryPointYear = strpos($years[$i], ',');
	$exitPointYear = strrpos($years[$i], ',');
	$len = $exitPointYear - $entryPointYear;
	if ($len == 0) {
		$years[$i] = substr($years[$i], NULL, 4);
	}
	$years[$i] = substr($years[$i], ($entryPointYear + 2), 4);

	$entryPointDirector = strpos($directors[$i], ': ');
	$directors[$i] = substr($directors[$i], ($entryPointDirector + 1));
}

// var_export ($names);
// echo ("<br>");
// echo ("<br>");
// var_export ($ratings);
// echo ("<br>");
// echo ("<br>");
// var_export ($years);
// echo ("<br>");
// echo ("<br>");
// var_export ($directors);
// echo ("<br>");
// echo ("<br>");

$conn = new mysqli("127.0.0.1", "st", "password", "kpDB"); // Подключение к БД
if($conn->connect_error){
   die("Connection failed: " . $conn->connect_error);
}

$conn->query( // Создание таблицы "Movies", если она не существует
	"CREATE TABLE IF NOT EXISTS movies(
		id INT PRIMARY KEY AUTO_INCREMENT,
		movieName VARCHAR(100), 
		rating FLOAT,
		yearCreation INT, 
		director VARCHAR(50),
		dateAdded DATE
		)"
	);
$stmt = $conn->prepare("INSERT INTO movies (movieName, rating, yearCreation, director, dateAdded) 
						VALUES (?,?,?,?,NOW())"); // Prepared statement для загрузки данных в БД
for ($l = 0; $l < 20; $l++){ // Выполнение prepared statment'а (сейчас не работает)
	$movie = $names[l];
	$rating = $ratings[l];
	$yearCreation = $years[l];
	$director = $directors[l];
	$stmt->bind_param("sdisb", $movie, $rating, $yearCreation, $director, $dateAdded);
	$stmt->execute();

	// echo ($movie . "</br>");
	echo ("Error: %s.\n" . $stmt->error . "</br>");	
}

$stmt->close(); // Закрытие потока prepared statement
$conn->close(); // Закрытие потока связи с БД

