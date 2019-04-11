Sample composer.json for a project that relies on packages that use composer-scaffold:
```
{
  "name": "my/project",
  "require": {
    "drupal/composer-scaffold": "*",
    "composer/installers": "^1.2",
    "cweagans/composer-patches": "^1.6.5",
    "drupal/core": "^8.8.x-dev",
    "pantheon-systems/d8-scaffold-files": "^1"
  },
  "config": {
    "optimize-autoloader": true,
    "sort-packages": true
  },
  "extra": {
    "composer-scaffold": {
      "allowed-packages": [
        "fixtures/drupal-core-fixture",
        "fixtures/scaffold-override-fixture"
      ],
      "locations": {
        "web-root": "./docroot"
      },
      "symlink": true,
      "file-mapping": {
        "drupal/core": {
           "assets/.htaccess": false
        }
        "my/project": {
           "my-assets/robots.txt": "[web-root]/robots.txt"
        }
      }
    }
  }
}
```

Sample composer.json for composer-scaffold files in drupal/core:

```
{
  "name": "drupal/core",
  "extra": {
      "composer-scaffold": {
        "file-mapping": {
          "drupal/core": {
            "assets/.htaccess": "[web-root]/.htaccess",
            "assets/index.php": "[web-root]/index.php",
            "assets/robots.txt": "[web-root]/robots.txt",
            "assets/sites/default/default.services.yml": "[web-root]/sites/default/default.services.yml"
            "assets/sites/default/settings.php": "[web-root]/sites/default/settings.php"
          }
        }
      }
    }
  }
}
```

Sample composer.json for a library that implements composer-scaffold:

```
{
  "name": "pantheon-systems/d8-scaffold-files",
  "extra": {
      "composer-scaffold": {
        "file-mapping": {
          "pantheon-systems/d8-scaffold-files": {
            "assets/sites/default/settings.php": "[web-root]/sites/default/settings.php"
          }
        }
      }
    }
  }
}
```

Patch a file after it's copied:

```
"post-composer-scaffold-cmd": [
  "cd docroot && patch -p1 <../patches/htaccess-ssl.patch"
]
```