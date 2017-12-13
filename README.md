Пример вызова из комендной строки:
```
php run.php feed:check urlsmap.yaml
```
Здесть urlsmap.yaml - файл с списком ссылок, и правилами их обработки

Пример файла urlsmap.yaml:
```
urls:
  - feedType: 'csv'
    feed: 'http://api.biglion.ru/api.php?method=get_google_feed&type=csv&site=7&category=131&city=407'
    site: 'https://abakan.biglion.ru/services/'
```
