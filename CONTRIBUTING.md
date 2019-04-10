You may test this plugin by running the following commands:

```
git clone git@github.com:grasmash/composer-scaffold.git
cd composer-scaffold
composer install
mkdir ../composer-scaffold-test
cp fixtures/composer.json ../composer-scaffold-test/
cd ../composer-scaffold-test
composer install
composer composer:scaffold
```