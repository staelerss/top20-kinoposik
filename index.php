<?php
function getAtribute($file, $pattern) {
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

$names = getAtribute($file, $patternName);
$ratings = getAtribute($file, $patternRating);
$years = getAtribute($file, $patternYear);
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

// print_r ($names);
// echo ("<br>");
// echo ("<br>");
// print_r ($ratings);
// echo ("<br>");
// echo ("<br>");
// print_r ($years);
// echo ("<br>");
// echo ("<br>");
// print_r($directors);

$conn = new mysqli("127.0.0.1", "st", "password", "kpDB");
$conn->query(
	"CREATE TABLE IF NOT EXISTS movies(
		id INT PRIMARY KEY AUTO_INCREMENT,
		movieName VARCHAR(100) NOT NULL, 
		rating FLOAT NOT NULL,
		yearCreation INT NOT NULL, 
		director VARCHAR(50) NOT NULL,
		dateAdded DATE NOT NULL
		)"
	);

for ($i = 0; $i < 20; $i++) {
	$conn->query("INSERT INTO movies(movieName, rating, yearCreation, director, dateAdded) 
				  VALUES (" . $names[$i] . ", " . $ratings[$i] . ", " . $years[$i] . ", " . $directors[$i] . ", NOW()");
}

// if($conn->connect_error){
//     die("Ошибка: " . $conn->connect_error);
// }
// echo "Подключение успешно установлено";
$conn->close();

