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
        "drupal/core",
      ],
      "locations": {
        "web-root": "./docroot"
      },
      "symlink": true,
      "overwrite": "always",
      "file-mapping": {
        "[web-root]/.htaccess": false,
        "[web-root]/robots.txt": "assets/robots-default.txt"
      }
    }
  }
}
```

Sample composer.json for drupal/core, with assets placed in a different project:

```
{
  "name": "drupal/core",
  "extra": {
    "composer-scaffold": {
      "allowed-packages": [
        "drupal/assets",
      ]
    }
  }
}
```

Sample composer.json for composer-scaffold files in drupal/assets:

```
{
  "name": "drupal/assets",
  "extra": {
    "composer-scaffold": {
      "file-mapping": {
        "[web-root]/.csslintrc": "assets/.csslintrc",
        "[web-root]/.editorconfig": "assets/.editorconfig",
        "[web-root]/.eslintignore": "assets/.eslintignore",
        "[web-root]/.eslintrc.json": "assets/.eslintrc.json",
        "[web-root]/.gitattributes": "assets/.gitattributes",
        "[web-root]/.ht.router.php": "assets/.ht.router.php",
        "[web-root]/.htaccess": "assets/.htaccess",
        "[web-root]/sites/default/default.services.yml": "assets/default.services.yml",
        "[web-root]/sites/default/default.settings.php": "assets/default.settings.php",
        "[web-root]/sites/example.settings.local.php": "assets/example.settings.local.php",
        "[web-root]/sites/example.sites.php": "assets/example.sites.php",
        "[web-root]/index.php": "assets/index.php",
        "[web-root]/robots.txt": "assets/robots.txt",
        "[web-root]/update.php": "assets/update.php",
        "[web-root]/web.config": "assets/web.config"
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
            "[web-root]/sites/default/settings.php": "assets/sites/default/settings.php"
        }
      }
    }
  }
}
```

@todo: Append to robots.txt:

```
{
  "name": "pantheon-systems/d8-scaffold-files",
  "extra": {
      "composer-scaffold": {
        "file-mapping": {
            "[web-root]/robots.txt": {
              "path": "assets/my-robots-additions.txt",
              "mode": "append"
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