Sample composer.json for a project that relies on packages that use composer-scaffold:
```
{
  "name": "my/project",
  "require": {
    "drupal/composer-scaffold": "*",
    "drupal/core": "^8.8",
    "pantheon/scaffold-files": "*"
  },
  "config": {
    "optimize-autoloader": true,
    "sort-packages": true
  },
  "extra": {
    "composer-scaffold": {
      "allowed-packages": {
        "drupal/core": "*",
        "pantheon/scaffold-template": "*"
      },
      "locations": {
        "web-root": "./docroot"
      },
      "symlink": true,
      "file-mapping": {
        "drupal/core": {
          "assets/.htaccess": false,
          "assets/robots.txt": "[web-root]/robots-default.txt"
        }
      }
    }
  }
}
```

Sample composer.json for a library that implements composer-scaffold:

```
"name": "drupal/core",
"extra": {
    "composer-scaffold": {
      "file-mapping": {
        "drupal/core": {
          "assets/.htaccess": "[web-root]/.htaccess",
          "assets/index.php": "[web-root]/index.php",
          "assets/robots.txt": "[web-root]/robots.txt"
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