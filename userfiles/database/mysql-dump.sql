
CREATE TABLE IF NOT EXISTS `blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(4) NOT NULL,
  `name` varchar(200) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_active` (`id`,`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `garbage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(100) NOT NULL,
  `row_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module` (`module`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(100) NOT NULL,
  `row_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `operation` varchar(30) NOT NULL,
  `date` datetime NOT NULL,
  `name` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module` (`module`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `title` varchar(250) NOT NULL,
  `url` varchar(150) NOT NULL,
  `redirect` varchar(150) NOT NULL,
  `order` int(11) NOT NULL,
  `content` text NOT NULL,
  `active` tinyint(4) NOT NULL,
  `in_menu` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_active` (`id`,`active`),
  KEY `parent_active_in_menu` (`parent`,`active`,`in_menu`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6;

INSERT INTO `pages` (`id`, `parent`, `name`, `title`, `url`, `redirect`, `order`, `content`, `active`, `in_menu`) VALUES
(1,-1,'Добро пожаловать','','index','',1,'<h2>Модели<br></h2><p>Файлы моделей расположены в папке /models/<br><a href="http://mv-framework.ru/predustanovlennye-modeli/" target="_blank">Подробнее о моделях</a></p><h2>Шаблоны</h2><p>Файлы шаблонов расположены в папке /views/<br><a href="http://mv-framework.ru/sozdanie-novogo-shablona/" target="_blank">Подробнее о шаблонах</a></p><h2>Маршрутизация</h2><p>Файл с маршрутами /config/routes.php<br><a href="http://mv-framework.ru/obshie-principy-shablonov/" target="_blank">Подробнее о маршрутизации</a></p>',1,1),
(2,-1,'Страница не найдена','','e404','',5,'<p>Запрошенная страница не найдена на сайте</p>',1,0),
(3,-1,'О проекте','','about','',2,'<p>Основная идея MV framework - упростить и ускорить создание сайтов и веб-приложений при помощи встроенного CMF, позволяющего управлять контентом через панель администратора.</p><ul>
<li>полностью объектно-ориентированный подход</li>
<li>автозагрузка классов моделей и плагинов</li>
<li>отсутствие констант и глобальных переменных</li>
<li>абстракция базы данных</li>
<li>возможность использования разных СУБД (MySQL и SQLite)</li>
<li>применение популярных PHP паттернов (Sigleton, Active Record)</li>
<li>обновляемое ядро и административный интерфейс (обратная совместимость)</li>
</ul><p>Административная панель автоматически создает интерфейс для управления моделями. Все активные модели имеют свой раздел в административной панеле, через который можно создавать, редактировать и удалять записи.</p>',1,1),
(4,-1,'Документация и поддержка','','documentation','',3,'<p>Документация и примеры кода <a href="http://mv-framework.ru" target="_blank">http://mv-framework.ru</a></p><p><a href="http://mv-framework.ru" target="_blank"></a>Вопросы и ответы <a href="http://mv-framework.ru/questions/" target="_blank">http://mv-framework.ru/questions/</a></p><p><a href="http://mv-framework.ru/questions/" target="_blank"></a>Обратная связь <a href="http://mv-framework.ru/feedback/" target="_blank">http://mv-framework.ru/feedback/</a></p>',1,1),
(5,-1,'Форма','','form','',4,'<p>Пример формы для отправки email сообщения или занесения записи в базу данных.</p>',1,1);

CREATE TABLE IF NOT EXISTS `seo` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `settings` (`key`, `value`) VALUES
('files_counter', '1');

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `email` varchar(100) NOT NULL,
  `login` varchar(100) NOT NULL,
  `password` varchar(32) NOT NULL,
  `date_registered` datetime NOT NULL,
  `date_last_visit` datetime NOT NULL,
  `settings` text NOT NULL,
  `active` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email_active` (`email`,`active`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

INSERT INTO `users` (`id`, `name`, `email`, `login`, `password`, `date_registered`, `date_last_visit`, `settings`, `active`) VALUES
(1, 'Root', '', 'root', '63a9f0ea7bb98050796b649e85481845', NOW(), '', '', 1);

CREATE TABLE IF NOT EXISTS `users_logins` (
  `login` varchar(100) NOT NULL,
  `date` datetime NOT NULL,
  `user_agent` varchar(32) NOT NULL,
  `ip_address` varchar(32) NOT NULL,
  KEY `ip_date` (`ip_address`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users_passwords` (
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `password` varchar(32) NOT NULL,
  `code` varchar(30) NOT NULL,
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users_rights` (
  `user_id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `create` tinyint(4) NOT NULL,
  `read` tinyint(4) NOT NULL,
  `update` tinyint(4) NOT NULL,
  `delete` tinyint(4) NOT NULL,
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users_sessions` (
  `user_id` int(11) NOT NULL,
  `session_id` varchar(32) NOT NULL,
  `ip_address` varchar(20) NOT NULL,
  `user_agent` varchar(32) NOT NULL,
  `last_hit` datetime NOT NULL,
  UNIQUE KEY `users_sessions_all` (`user_id`,`session_id`,`ip_address`,`user_agent`),
  UNIQUE KEY `users_sessions_uid_sid` (`user_id`,`session_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(100) NOT NULL,
  `row_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `content` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `model_row_id` (`model`,`row_id`),
  KEY `model_version` (`model`,`version`),
  KEY `model_row_id_version` (`model`,`row_id`,`version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(200) NOT NULL,
  `content` longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cache_clean` (
  `key` varchar(200) NOT NULL,
  `model` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `cache` ADD PRIMARY KEY (`key`);

ALTER TABLE `cache_clean` ADD KEY `model` (`model`);
