# composer-scaffold

Composer plugin for placing scaffold files (like `index.php`, `update.php`, …) from the `drupal/core` project into their desired location inside the web root. Only individual files may be scaffolded with this plugin.

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

### Defining Scaffold Files

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

### Defining Project Locations

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
It is also possible to prepend to a scaffold file instead of, or in addition to appending by including a "prepend" entry that provides the relative path to the file to prepend to the scaffold file.

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

The `allowed-packages` configuration setting contains an orderd list of package names that will be used during the scaffolding phase.
```
"allowed-packages": [
  "drupal/core",
],
```
### file-mapping

The `file-mapping` configuration setting consists of a map from the destination path of the file to scaffold to a set of properties that control how the file should be scaffolded.

The available properties are as follows:

- mode: One of "replace", "append" or "skip". 
- path: The path to the source file to write over the destination file.
- prepend-path: The path to the source file to prepend to the destination file, which must always be a scaffold file provided by some other project.
- append-path: Like `prepend-path`, but appends content rather than prepends.
- overwrite: If `false`, prevents a `replace` from happening if the destination already exists.

The mode may be inferred from the other properties. If the mode is not specified, then the following defaults will be supplied:

- replace: Selected if a `path` property is present, or if the entry's value is a string rather than a property set.
- append: Selected if a `prepend-path` or `append-path` property is present.
- skip: Selected if the entry's value is a boolean `false`.

Examples:
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
The short-form of the above example would be:
```
"file-mapping": {
  "[web-root]/sites/default/default.settings.php": "assets/sites/default/default.settings.php",
  "[web-root]/sites/default/settings.php": {
    "path": "assets/sites/default/settings.php",
    "overwrite": false
  },
  "[web-root]/robots.txt": {
    "prepend-path": "assets/robots-prequel.txt",
    "append-path": "assets/robots-append.txt"
  },
  "[web-root]/.htaccess": false
}
```
Note that there is no distinct "prepend" mode; "append" mode is used to both append and prepend to scaffold files. The reason for this is that scaffold file entries are identified in the file-mapping section keyed by their destination path, and it is not possible for multiple entries to have the same key. If "prepend" were a separate mode, then it would not be possible to both prepend and append to the same file.

### locations

The `locations` configuration setting contains a list of named locations that may be used in placing scaffold files. The only required location is `web-root`. Other locations may also be defined if desired.
```
"locations": {
  "web-root": "./docroot"
},
```
### overwrite

The top-level `overwrite` property defines the defaults value for the `overwrite` property of `file-mapping` elements. It defaults to `true`; a project may set it to `false` to disable updating scaffold files. Note that `append` operations override the `overwrite` option, and force a fresh copy every time.
```
"overwrite": true,
```
### symlink

The `symlink` property causes `replace` operations to make a symlink to the source file rather than copying it. This is useful when doing core development, as the symlink files themselves should not be edited. Note that `append` operations override the `symlink` option, to prevent the original scaffold assets from being altered.
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

### drupal-composer/drupal-scaffold

Previous versions of drupal-scaffold (see community project, [drupal-composer/drupal-scaffold](https://github.com/drupal-composer/drupal-project)) downloaded each scaffold file directly from its distribution server (e.g. `https://cgit.drupalcode.org`) to the desired destination directory. This was necessary, because there was no subtree split of the scaffold files available. Copying the scaffold assets from projects already downloaded by Composer is more effective, as downloading and unpacking archive files is more efficient than downloading each scaffold file individually.

### composer/installers

The [composer/installers](https://github.com/composer/installers) plugin is similar to this plugin in that it allows dependencies to be installed in locations other than the `vendor` directory. However, Composer and the `composer/installers` plugin have a limitation that one project cannot be moved inside of another project. Therefore, if you use `composer/installers` to place Drupal modules inside the directory `web/modules/contrib`, then you cannot also use `composer/installers` to place files such as `index.php` and `robots.txt` into the `web` directory. The drupal-scaffold plugin was created to work around this limitation.
