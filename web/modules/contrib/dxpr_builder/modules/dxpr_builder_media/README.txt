# DXPR Builder Media
===

## Requirements

- Inline Entity Form
- Entity Browser
- Entity Embed
- Media
- Media Browser
- Views

## Installation

- Download Inline Entity Form from Drupal.org
- Download Entity Embed from Drupal.org
- Download Entity Browser from Drupal.org
- Download Media Entity Browser from Drupal.org
- Enable core Media
- Enable core Media Browser
- Enable core Views
- Enable DXPR Builder Media

## Usage

This module is largely a set of configuration files to provide a basic Entity
Browser for Media - with some custom styling and interaction for the Browser
view. As such the documentation for Entity Browser and Media in general will
explain how to customise the installed Media Entity Browsers.

https://drupal-media.gitbooks.io/drupal8-guide/content/modules/entity_browser/intro.html

Once installed, changes to the configuration are managed outside of this module.

### Configuration

On installation the Browser isn't visible. You need to add it to an Entity
Embed button for use though the WYSIWYG or to an Media Entity Reference Fields
with the Inline Entity Form - Complex field widget.

- For WYSIWYG Entity Embedding use the iFrame Browser
(/admin/config/content/entity_browser/dxpr_builder_media).
- For Media Entity Reference Fields use the Modal Browser
(/admin/config/content/entity_browser/dxpr_builder_media).

### Credit

This module is based on Media Enttiy Browser:
https://www.drupal.org/project/media_entity_browser
