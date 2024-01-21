# Kindle Weather Display

![Weather Display Example](weather.png)

## Setup

1. install apache2 with ImageMagic
2. Create `api_key.php` and place your accoweather API key in it
```php
<?php

$API_KEY = "...";

?>
```

3. Pull the latest forecast with cron.

```bash
crontab -e

59 * * * * cd /var/www/html; php pull_forecast.php
```

4. Point your Kindle etc. to `http://myserver.com/weather.php`


