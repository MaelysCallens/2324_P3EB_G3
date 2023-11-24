# DXPR Theme

For user documentation and support please check:
https://app.dxpr.com/hc/documentation

For development documentation and support please check:
https://app.dxpr.com/hc/documentation/internal

## Contributing Guidelines

Before you write any code for this project please also check 
https://github.com/dxpr/dxpr_maven/blob/main/CONTRIBUTING.md

## [WARNING2] Save theme settings form after updating theme CSS

Because DXPR Theme integrates with the color module, changes to its CSS may
not immediately take effect.
When the theme is configured with custom colors in the theme settings form
the color module will save a copy of the recolored theme CSS files inside
the Drupal files folder. When updating the theme CSS files in the theme
folder these changes might not take effect because the browser is loading
the copies of the theme's CSS files from the files folder. To fix this you
have to save the theme settings form so that the color module will create
new copies of the theme's CSS files that include your latest changes.

# Continuous Integration / Automation

## References

- https://www.drupal.org/docs/develop/standards
- https://www.drupal.org/node/1587138
- https://www.drupal.org/node/1955232
- https://github.com/shaundrong/eslint-config-drupal-bundle#readme

## Development Setup

You need to install `docker` and `docker-compose` to your workstation.
You can keep using whatever to run your webserver,
we just use docker to run our scripts.


### How to watch and build files

```bash
$ DEV_WATCH=true docker-compose up dev
```

### How to run eslint check

```bash
$ docker-compose up dev eslint
```

### How to run eslint check with html report

```bash
$ REPORT_ENABLED=true docker-compose up dev eslint
```

After it finishes, open `out/eslint-report.html` file to see report in details.


### How to run eslint auto fix

```bash
$ docker-compose up dev eslint-auto-fix
```

### How to run Drupal lint check

```bash
$ docker-compose up drupal-lint
```

### How to run Drupal lint auto fix

```bash
$ docker-compose up drupal-lint-auto-fix

### How to run drupal-check

```bash
$ docker-compose up drupal-check
# or
$ docker-compose run --rm drupal-check
```

### Using our Demo sites for local development

You can use one of three demo sites to save time in settings up a Drupal site
with DXPR and using its features: dxpr_basic_demo, dxpr_qa_demo, and
dxpr_logistics_demo. The Logistics Demo is the best showcase of DXPR Theme
because it makes use of more of its theme settings.

#### Instructions setting up the Lightning DXPR site

https://github.com/dxpr/lightning_dxpr_project

#### Video demo of settings up the Lightning DXPR site with QA demo

https://www.youtube.com/watch?v=AYEIkdiWuC4

#### Video demo of your local site over from QA to Logistics demo

https://www.youtube.com/watch?v=_NnUTFC39n4
