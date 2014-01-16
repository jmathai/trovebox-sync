Trovebox Sync
=============

Still in beta.

Requirements.
  * PHP 5.3
  * Sqlite3

## Initialize local database

Create a database. Can be named anything but you'll reference it later in `.secrets.json`. In this example we'll use *db.sqlite*.
```sh
sqlite3 db.sqlite
sqlite> .load db.sql
```

## Set up credentials

Create a file named `.secrets.json` with the following values.
```json
{
  "host":"your.trovebox.domain.com",
  "consumerKey":"***************************",
  "consumerSecret":"**********",
  "token":"***************************",
  "tokenSecret":"**********",
  "target":"/path/to/local/folder/to/store/photos"
}

```

## Run the script

You can run this script as frequently as needed and it will only download new photos.
```php
user# ./sync.php
```

## Not yet implemented

  * Does not support deleting local files (2 way sync)
  * Does not support updating new metadata
