# mS3 Commerce FX

## Contents
- [Prerequisites](#prerequisites)
- [Usage](#usage)
  - [Quickstart guide](#quickstart-guide)
  - [FLUID Templating](#fluid-templates)
  - [ViewHelpers](#viewhelpers)
  - [Search](#search)
  - [Detailed configuration](#detailed-configuration)
- [cart Integration](#cart-integration)

## Prerequisites

## Usage
The mS3 Commerce FX provides a FE Plugin that can be added on a Typo3 page to
display catalogue content from mS3 PIM. The plugin offers different view types for
different purposes.

The extension comes with a basic template for the most common `List` View. However,
it is designed that all templates are overridden by a custom package extension to
implement the required look&feel.

It also comes with some preconfigured TS that can be embedded in your TypoScript templates
to enable more advanced features, like AJAX filters, speaking URLs, etc.

### Quickstart Guide
- Add mS3 Commerce FX Plugin to page
  

- Set entry point in Plugin:
  - Set "Root level" (PIM Structure element)
  - Set "Root Element" (PIM Object for root)
    

- OR: Set default entry point in TypoScript:

      plugin.tx_ms3commercefx.settings {
          rootGuid = (GUID of root object in PIM)
          shopId = 1
      }

- Overwrite template and partial root path:

      plugin.tx_ms3commercefx.view {
          templateRootPaths {
              10 = EXT:yourextension/Resouces/Private/Templates/Commerce
          }
          partialRootPaths {
              10 = EXT:yourextension/Resouces/Private/Partials/Commerce
          }
      }
  With this, Typo3 will look into the provided paths first for templates/partials
  for mS3Commerce


## FLUID Templates
The templating revolves around the PIM Object entities. The various entry points
to the extension usually provide such an object or list thereof (e.g. search results).

### Object
The object represents a single object from PIM. This is a "group" (structuring level) or
a "product".

#### Properties


### ViewHelpers

## Additional features
### Speaking URLs
### Search
### Filters

## Detailed Configuration

## Technical details
### Lazy bulk loading

## cart Integration
