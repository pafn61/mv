<?
/**
 * MV - content management framework for developing internet sites and applications.
 * Released under the terms of BSD License.
 * http://mv-framework.ru
 */

//Главный конфигурационный файл со всеми настройками и автозапусками
require_once "config/autoload.php";

//Установите 1 Для просмотра рабочего времени и sql-запросов
$debug = new Debug(1); 

//Основной объект сайта также содержит все модули объектов
$mv = new Builder();

//Маршрутизатор ссылается на включение необходимого вида для отображения страницы
include_once $mv -> router -> defineRoute();

//Если 1 был выбран выше отображение данных отладки
$debug -> displayInfo($mv -> router);
?>