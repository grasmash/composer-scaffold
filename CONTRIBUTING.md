You may test this plugin by running the following commands:

```
git clone git@github.com:grasmash/composer-scaffold.git
cd composer-scaffold
composer install
rm -rf ../composer-scaffold-test
cp -r tests/fixtures ../composer-scaffold-test
cd ../composer-scaffold-test/top-level-project
composer install
composer composer:scaffold
```