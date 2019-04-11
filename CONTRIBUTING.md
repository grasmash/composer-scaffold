To contribute to this project, first install it locally:

```
git clone git@github.com:grasmash/composer-scaffold.git
cd composer-scaffold
composer install
```

Run the automated tests:
```
composer test
```

Ad-hoc testing:

```
rm -rf ../composer-scaffold-test
cp -r tests/fixtures ../composer-scaffold-test
cd ../composer-scaffold-test/top-level-project
composer install
composer composer:scaffold
```