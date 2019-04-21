# composer-scaffold

Composer plugin for placing scaffold files (like `index.php`, `update.php`, …) from the `drupal/core` project into their desired location inside the web root.

## Usage

Composer-scaffold is used by requiring `grasmash/composer-scaffold` in your project, and providing configuration settings in the `extra` section of your project's composer.json file. Additional configuration from the composer.jon file of your project's dependencies is also consulted in order to scaffold the files a project needs.

### Allowed Packages

Scaffold files are stored inside of projects that are required from the main project's composer.json file as usual. The scaffolding operation happens after `composer install`, and involves copying or symlinking the desired assets to their destination location. In order to prevent arbitrary dependencies from copying files via the scaffold mechanism, only those projects that are specifically permitted by the top-level project will be used to scaffold files.

Example: Permit scaffolding from the project `drupal/core`
```
  "name": "my/project",
  ...
  "extra": {
    "composer-scaffold": {
      "allowed-packages": [
        "drupal/core",
      ],
      ...
    }
  }
``` 
Allowing a package to scaffold files also permits it to delegate permission to scaffold to any project that it requires itself. This allows a package to organize its scaffold assets as it sees fit. For example, the project `drupal/core` may choose to store its assets in a subproject `drupal/assets`.

It is possible for a project to obtain scaffold files from multiple projects. For example, a Drupal project using a distribution, and installing on a specific web hosting service provider might take its scaffold files from:

- Drupal core
- Its distribution
- A project provided by the hosting provider
- The project itself

Each project allowed to scaffold by the top-level project will be used in turn, with projects declared later in the `allowed-packages` list taking precidence over the projects named before. The top-level composer.json itself is always implicitly allowed to scaffold files, and its scaffold files have highest priority.

### File Mapping

The placement of scaffold assets is under the control of the project that provides them, but the location is always relative to some directory defined by the root project -- usually the web root. For example, the scaffold file `robots.txt` is copied from its source location, `assets/robots.txt` into the web root in the snippet below.
```
{
  "name": "drupal/assets",
  ...
  "extra": {
    "composer-scaffold": {
      "file-mapping": {
        "[web-root]/robots.txt": "assets/robots.txt",
        ...
      }
    }
  }
}
```

### Defining Scaffold Locations

The top-level project in turn must define where the web root is located. It does so via the `locations` mapping, as shown below:
```
  "name": "my/project",
  ...
  "extra": {
    "composer-scaffold": {
      "locations": {
        "web-root": "./docroot"
      },
      ...
    }
  }
``` 
This makes it possible to configure a project with different file layouts; for example, either the `drupal/drupal` file layout or the `drupal-composer/drupal-project` file layout could be used to set up a project.

### Overwrite

By default, scaffold files overwrite whatever content exists at the target location. Sometimes a project may wish to provide the initial contents for a file that will not be changed in subsequent updates. This can be done by setting the `overwrite` flag to `false`, as shown in the example below:
```
{
  "name": "service-provider/d8-scaffold-files",
  "extra": {
    "composer-scaffold": {
      "file-mapping": {
        "[web-root]/sites/default/settings.php": {
          "mode": "replace",
          "path": "assets/sites/default/settings.php",
          "overwrite": false
        }
      }
    }
  }
}
```

### Altering Scaffold Files

Sometimes, a project might wish to use a scaffold file provided by a dependency, but alter it in some way. Two forms of alteration are supported: appending and patching.

The example below shows a project that appends additional entries onto the end of the `robots.txt` file provided by `drupal/core`:
```
  "name": "my/project",
  ...
  "extra": {
    "composer-scaffold": {
      "file-mapping": {
        "[web-root]/robots.txt": {
          "append-path": "assets/my-robots-additions.txt",
        }
      }
    }
  }
``` 
The example below demonstrates the use of the `post-composer-scaffold-cmd` hook to patch the `.htaccess` file using a patch.
```
  "name": "my/project",
  ...
  "scripts": {
    "post-composer-scaffold-cmd": [
      "cd docroot && patch -p1 <../patches/htaccess-ssl.patch"
    ]
  }
``` 

### Excluding Scaffold Files

Sometimes, a project might prefer to entirely replace a scaffold file provided by a dependency, and receive no further updates for it. This can be done by setting the value for the scaffold file to exclude to `false`:
```
  "name": "my/project",
  ...
  "extra": {
    "composer-scaffold": {
      "file-mapping": {
        "[web-root]/robots.txt": false
      }
    }
  }
``` 

## Specifications

Reference section for the configuration directives for the "composer-scaffold" section of the "extra" section of a `composer.json` file appear below.

### allowed-packages
```
"allowed-packages": [
  "drupal/core",
],
```
### file-mapping
```
"file-mapping": {
  "[web-root]/sites/default/default.settings.php": {
    "mode": "replace",
    "path": "assets/sites/default/default.settings.php",
    "overwrite": true
  },
  "[web-root]/sites/default/settings.php": {
    "mode": "replace",
    "path": "assets/sites/default/settings.php",
    "overwrite": false
  },
  "[web-root]/robots.txt": {
    "mode": "append",
    "prepend-path": "assets/robots-prequel.txt",
    "append-path": "assets/robots-append.txt"
  },
  "[web-root]/.htaccess": {
    "mode": "skip",
  }
}
```
### locations
```
"locations": {
  "web-root": "./docroot"
},
```
### overwrite
```
"overwrite": true,
```
### symlink
```
"symlink": true,
```
## Managing Scaffold Files

Scaffold files should be treated the same way that the `vendor` directory is handled. If you need to commit `vendor` (e.g. in order to deploy your site), then you should also commit your scaffold files. You should not commit your `vendor` directory or scaffold files unless it is necessary.

If a dependency provides a scaffold file with `overwrite` set to `false`, that file should be committed to your repository.

## Examples

Some full-length examples appear below.

Sample composer.json for a project that relies on packages that use composer-scaffold:
```
{
  "name": "my/project",
  "require": {
    "drupal/composer-scaffold": "*",
    "composer/installers": "^1.2",
    "cweagans/composer-patches": "^1.6.5",
    "drupal/core": "^8.8.x-dev",
    "service-provider/d8-scaffold-files": "^1"
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
      "overwrite": true,
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
  "name": "service-provider/d8-scaffold-files",
  "extra": {
    "composer-scaffold": {
      "file-mapping": {
        "[web-root]/sites/default/settings.php": "assets/sites/default/settings.php"
      }
    }
  }
}
```

Append to robots.txt:

```
{
  "name": "service-provider/d8-scaffold-files",
  "extra": {
    "composer-scaffold": {
      "file-mapping": {
        "[web-root]/robots.txt": {
          "append-path": "assets/my-robots-additions.txt",
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

## Related Plugins

Previous versions of drupal-scaffold (see community project, [drupal-composer/drupal-scaffold](https://github.com/drupal-composer/drupal-project)) downloaded each scaffold file directly from its distribution server (e.g. `https://cgit.drupalcode.org`) to the desired destination directory. 

