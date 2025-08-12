# Шаблон рассылки

Разработка...

## Добвить в файл `.htaccess`

`````
<Files cron.php>
	Order Allow,Deny
	Deny from all
</Files>
`````
Запрет доступа к файлу `cron.php` по ссылке.
