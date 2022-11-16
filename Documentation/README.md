# mS3 Commerce FX

## Contents
- [Prerequisites](#prerequisites)
- [Usage](#usage)
  - [Quickstart guide](#quickstart-guide)
  - [Utilities](#utilities)
  - [Languages & Shops](#languages-and-shops)
- [Extension functions](#extension-functions)
  - [Plugin functions](#plugin-functions)
    - [Object List](#object-list)
    - [Object Detail](#object-detail)
    - [Search Result](#search-result)
    - [Additional functions](#additional-functions)
  - [Caching](#caching)
- [FLUID Templating](#fluid-templates)
  - [Object Model](#object-model)
    - [Object](#object)
    - [Attribute](#attribute)
    - [Structure Element](#structure-element)
    - [Categorization](#categorization)
    - [Relation](#relation)
  - [ViewHelpers](#viewhelpers)
    - [Link.Object](#ms3linkobject)
    - [Image](#ms3image)
    - [Structure.IsChildOf](#ms3structureischildof)
    - [AjaxSearch.From](#ms3ajaxsearchform)
    - [AjaxSearch.Control](#ms3ajaxsearchcontrol)
    - [AjaxSearch.Result](#ms3ajaxsearchresult)
- [Additional Features](#additional-features)
  - [Speaking URLs](#speaking-urls)
  - [Full Text Search](#full-text-search)
  - [Ajax Filters](#ajax-filters)
  - [Object restrictions](#user-and-market-based-restrictions)
  - [Cart integration](#cart-integration)
- [Detailed configuration](#detailed-configuration)
- [Technical details](#technical-details)
- [PHP Extending](#php-interface)
  - [Object Model](#models)
  - [Object Repository](#repository)
  - [Controllers](#controllers-and-actions)
  - [Hooks](#hooks)

## Prerequisites

- PHP 7.2+ / 8.0+
- Typo3 10, 11
- dataTransfer 6.5+
- For cart integration: cart 8+

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
- Install and set up dataTransfer (cf. [dataTransfer documentation](https://github.com/goodson-softwaresolutions-gmbh/mS3Commerce-dataTransfer/tree/master/documentation))

- Add mS3 Commerce FX Plugin to page

- Set entry point in Plugin:
  - Set "Root level" (PIM Structure element)
  - Set "Root Element" (PIM Object for root)
  - Set the "template file" to the template file to use

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
  for mS3Commerce (cf. [Typo3 FLUIDTEMPLATE docs](https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/ContentObjects/Fluidtemplate/Index.html))

### Utilities
The dataTransfer provides a DB Viewer to see what data objects are import form mS3 PIM.
It is found in on the webserver at `https://host/dataTransfer/viewdb.php`
Check the [dataTransfer documentation](https://github.com/goodson-softwaresolutions-gmbh/mS3Commerce-dataTransfer/tree/master/documentation#viewdbphp) for details. 

### Languages and Shops
mS3 Commerce can handle multiple languages and product assortments (called "market") in a single instance. A language/assortment
combination is called "Shop".

The PIM data in each shop is independent of other Shops and can be updated individually. 
Within a Shop, every object, attribute, etc. has a unique, but non-persistent ID in the Shop's range 
(e.g. 1000000 - 1999999 belong to Shop 1). 

Besides these transient IDs, objects have a persistent ObjectId. The ObjectId + Shop Id is called a Context-Id and
uniquely and persistently identifies an object in a Shop.

## Extension functions
mS3 Commerce FX provides various entry points for different use cases.

### Plugin functions
The Extension offers a Frontend Plugin that can be added as content element to pages.
Multiple plugins can be added to a single page, with different templates.

#### Object List
Used for displaying a list of mS3 PIM Objects, e.g. the Products in a Chapter.
The function provides the following objects to the template:
- `object`: The currently viewed PIM Object (see [Object](#object))
- `allAttributes`: A list of all available PIM attributes

#### Object Detail
Similar to object list, without pagination in mind.
The functionality is the same, but enables different templates for Lists and Details
- `object`: The currently viewed PIM Object (see [Object](#object))
- `allAttributes`: A list of all available PIM attributes

#### Search Result
Displays a list of full-text search results. The search term must be sent using a
`GET`/`POST` parameter of name `term`.

Variables provided:
- `result.objects`: List of found objects
- `result.page`: A pagination object, with nr of hits, next page, current page, objects per page, etc.
- `term`: The original search term

For details on how to use search, see [Search](#full-text-search)

### Additional Functions
mS3 Commerce FX offers additional functions and provides TypoScript files for ease of integration.

The TS files can be found in `EXT:ms3commercefx/Configuration/TypoScript`, and expose preconfigured
snippets in the `ms3commercefx` object path. These can be included in a size , e.g. like this:

    <INCLUDE_TYPOSCRIPT: source="FILE:EXT:ms3commercefx/Configuration/menu.ts">
    page.20 < ms3commercefx.menu

#### Menu
The menu script is used to display a static navigation. It does not create a Typo3 HMENU object, instead
exposes the same functionality as the List or Detail views. Thus, a full FLUID Template can be used
for the mS3 Commerce FX Menu.

Unlike the List and Detail views, which will adjust the currently displayed object depending on the current
page (i.e. query parameters), the Menu view will always start at the same root object, independent of parameter,
or if there is a mS3 Commerce plugin present in the page.

The view will receive following parameters:
- `object`: The root object of the menu
- `current`: The currently displayed content object

#### AjaxSearch
The ajax_search script can be used to integrate a dynamic filter on the website. 

AjaxSearch is designed to work with a normal List template, that uses the `AjaxSearch.form`, `AjaxSearch.result`
and various `AjaxSearch.control` view helpers to display the input form. With this, the form elements
and placeholders for the result are designed.

The requests are sent to a page that includes the controller as predefined in the ajax_search.ts script.
It will execute the search and filtering, and render the results into a JSON object. The controller needs
a template file configured in `plugin.tx_ms3commercefx_ajaxsearch.settins.resultTemplate` that will
receive the result objects and renders them.

The result template will receive the following variables:
- `result.objects`: The search result object
- `result.page`: A pagination object
- `settings`: All settings passed to the AjaxSearch Controller (can be used as pass through)

Controller will return a JSON with the following content:
- `filter`: The possible and selected filter values
- `page`: A pagination object
- `result`: The rendered result by the configured template

The form will update the `AjaxSearch.result` element with the result as rendered by the Result Template,
and the values of all `AjaxSearch.control` input elements.

### Caching
The Front End Plugin functions are usually not cached, so that all content is re-rendered on every page load.

With the global parameter `MS3C_TYPO3_CACHED` (see [dataTransfer documentation](https://github.com/goodson-softwaresolutions-gmbh/mS3Commerce-dataTransfer/tree/master/documentation#typo3-specific-parameters)), this can be changed.
If this parameter is set to 1, the Object List, Menu, and Ajax Form elements are cacheable content objects
and handled accordingly by Typo3. 

This is another distinguishing property between the List and Detail object views.

The cache of pages containing cached mS3 Commerce FX elements will automatically cleared when new PIM data
is imported into the website.

## FLUID Templates
The templating revolves around the PIM Object entities. The various entry points
to the extension usually provide such an object or list thereof (e.g. search results).

### Object Model

#### Object
The object represents a single object from PIM. This is a "group" (structuring level) or
a "product".

Each object belongs to a PIM Structure Level (e.g. chapter, product group, article, ...). 
The object hierarchy is represented by a Menu structure. An object may be referenced multiple times
in the Menu structure

##### Properties

- `id`: A numeric Id for this object. This Id is not persistent and may change with updates!
- `menuId`: The numeric Id of the hierarchy occurrence of this object. This Id is not persistent and may change with updates!
- `guid`: The global persistent Id of the object hierarchy occurrence
- `objectId`: The global persistent Id of the object
- `entityType`: The type of object (Product or Group)
- `isProduct`: Whether the object is a product
- `isGroup`: Whether the object is a group
- `name`: The object's unique name in PIM (in this Structure Level)
- `auxiliaryName`: The objects language dependent display name
- `structureElement` The structure element this object belongs to (see [Structure Element](#structure-element))
- `parentObject`: The object's direct parent
- `parentPath`: A list of the full path of objects in the hierarchy, starting at the shop root
- `children`: A list of all direct child objects
- `firstChild`: The first direct child object (see [Lazy bulk loading](#lazy-bulk-loading) for usage)
- `attributes`: The object's attributes (see [Attribute](#attribute))
- `categorizations`: The object's categorizations (see [Categorizations](#categorization))
- `relations`: The object's relations to other objects (see [Relations](#relation))

If an object is a Product, it has these additional properties:

- `basketQuantity`: Number of items in the basket
- `prices`: The product's prices (including all tier prices, sorted in ascending minimum quantity)
- `price`: The product's default price
- Price objects have the following properties:
  - `productGuid`: The product's guid (as back-reference)
  - `user`: The user this price is valid for (for customer specific prices)
  - `market`: The market this price refers to (for county/market specific prices)
  - `vpe`: The packaging unit this price belongs to (piece, packet, ...)
  - `startQty`: The minimum quantity this price is valid
  - `price`: The price
- `availabilities`: The product's availabilities
- `availability`: The product's default availability
- Availability objects have the following properties:
  - `productGuid`: The product's guid (as back-reference)
  - `market`: The market this availability refers to (for county/market specific availability)
  - `availablity`: The availability value

#### Attribute
An attribute holds attribute meta-data as well as the value for a certain object.

##### Name mangling
To satisfy the syntax requirements of Fluid variables, the Attributes' names are mangled to be accessible in Fluid.
Every non-alphanumeric character (e.g. not in a-z, 0-9) will be replaced by a `_`. 
Thus, for example `Länge in mm` becomes `L_nge_in_mm`

##### Inheritance
PIM objects in different hierarchies can have Attributes with the same name. The value of the attribute will
then be the _lowest_ existing value in the hierarchy.

To access a specific value, the Structure Element's name can be prepended to the attribute name.

E.g.: for a Hierarchy Productgroup -> Product -> Article:
- `Productgroup_Description` = 1
- `Article_Description` = 2
- `Description` at the Productgroup level = 1
- `Description` at the Product level = 1
- `Description` at the Article level = 2

##### Properties
These properties exist for a pure Attribute:

- `name`: The attribute's unique name as in PIM
- `auxiliaryName`: The Structure element name + attribuzte name
- `saneName`: The mangled name
- `saneAuxiliaryName`: The mangled auxiliary name
- `languageId`: The language id of the shop
- `marketId`: The market id of the shop
- `title`: The translated display title of the attribute
- `info`: Info field as in PIM
- `unitToken`: The unit token as in PIM (e.g. `mm` for attributes in Millimeter)

When loaded as a value of an object, it is an attribute value object, with these additional properties:

- `attribute`: The attribute itself (metadata as above can also be directly accessed, e.g. `v.title` = `v.attribute.title`)
- `contentPlain`: A Plain-Text representation of the value (this is the default if nothing is specified)
- `contentHtml`: A HTML encoded version with markup of the value
- `contentNumber`: If the value contains a single number, this is the numeric value of the value.
   Note that plain and HTML representation can have a number format applied, that makes them not
   a numeric value (e.g. 1'234,56 in swiss formatting for the number 1234.56)

#### Structure Element
Structure elements represent the different levels in a hierarchy.

This can be used to query what type of object is present, and act accordingly:

    <f:if condition="{object.structureElement.name} == 'Main Chapter'">
        <!-- ... --->
    </f:if>

##### Properties
- `guid`: Persistent ID
- `name`: The unique name
- `orderNr`: Ordering level. 1 is lowest (e.g. Article), -1 are meta-levels

#### Categorization
A categorization is a collection of attributes for a certain usage, prepared in PIM. It can be different
for different objects, and usually serves a specific purpose, e.g. Detail attributes for a Table, Filter attributes, ...

A categorization can be used to loop over the attributes (using `<f:for each>`), or use a "placed" categorization,
where the positions have a certain purpose (e.g. 1st attribute is product picture, 2nd is title, 3rd description, ...)

The categorizations are accessed from an object via the `categorizaions` path, which then contains the single
categorizations. The names are mangled as described above.

There exists a special categorization called `{Set}` (mangled to `_Set_`) that contains all attributes of the object.

A categorization always contains all attributes configured in the PIM, regardless whether the object has a value
for it or not. This can be used e.g. to define a table header.

##### Properties
- `attributes`: All attributes in the categorization
- `filledAttributes`: The categorization's attributes, that have a value for the object it was loaded from
- `hasValues`: If the object this categorization was loaded from has any filled values

##### Examples

    <table>
      <tr>
        <f:for each="{object.categorization.Table_Attributes}" as="a">
          <th>{a.title}</th>
        </f:for>
      </tr>
      <f:for each="{object.children}" as="c">
        <tr>
          <f:for each="{c.categorization.Table_Attributes}" as="a">
            <td>{a}</td>
          </f:for>
        </tr>
      </f:for>
    </table>

    -----

    <div class="product_box">
       <div class="img"><ms3:image src="{object.categorization.ProductBox.1}"/></div>
       <div class="title">{object.categorization.ProductBox.2.ContentPlain}</div>
       <div class="description">{object.categorization.ProductBox.3.ContentHtml}</div>
    </div>

#### Relation
A relation is a collection of other objects the current object refers to as configured in PIM, e.g. accessory, replacement, ...

The categorizations are accessed from an object via the `relations` path, which then contains the single
relations. The names are mangled as described above. The content is a list of objects

##### Properties

- `name`: The relation name
- `title`: The display title
- `parent`: The relation's source
- `child`: The relation's destination
- `text1`: An additional text belonging to the relation
- `amount`: An additional number belonging to the relation

### ViewHelpers

#### mS3:Link.Object
Creates a Link to another object. See [speaking urls](#speaking-urls) for details on how to generate nice URLs.

Arguments:
- (all arguments like `f:link.page`)
- `object`: The object to link to

#### mS3:Image
Provides similar functionality as built-in `f:image` view helpers, but will not throw an exception if the file
does not exist.

To use with a mS3 Commerce Object image value, pass the attribute value as `src` argument

Arguments:
- (all arguments like `f:image`)
- `srcOnly`: If true will return the path the image (equivalent to `f:uri.image`), otherwise a full `<img>` tag
- `placeholder`: A placeholder image to display, if the image does not exist. If no placeholder is given, nothing is returned
- `placeholderTransform`: Transformation (scaling, ...) to be applied on the placeholder

E.g.

    <ms3:image src="{object.Product_Image}" #
               maxWidth="500" 
               placeholder="fileadmin/not_found.png" 
               placeholderTransform="{width: 500}" />

#### mS3:Structure.IsChildOf
Checks if an object is a (grand-) child of another object

Arguments:
- `parent`: The parent to test
- `child`: The child to test
- `direct`: Only consider direct children (default true)
- `includeSelf`: Consider case when parent == child (default false)

E.g.

    <!-- inside a Menu controller template --> 
    <f:if condition="{ms3:structure.isChildOf(parent:{object},child:{current})}">
      <!-- current object is a DIRECT child of the menu root -->
    </f:if>

    ----

    <span class="{ms3:Structure.isChildOf(parent: themen, child: current, includeSelf: 1, direct: 0, then: 'open')}"/>

#### mS3:AjaxSearch.Form
Prepares the Form for an ajax search. This will render a `<form>` tag. All Controls must be inside this element.

The form will by default issue an AJAX request after loading to get initial filter values and results.
However, if the setting `initializeStaticResult` is set to 1, the rendering of initial filters is done on server-side.

Arguments:
- `pageUid`: The PID to send AJAX requests to (default current page)
- `root`: The root PIM object for accessing initial values
- `controlObjectName`: The JavaScript variable name that will contain the controller (default `ms3Control`)

#### mS3:AjaxSearch.Control
Represents a control for AJAX filter search.

For each type of control, a Fluid Partial must be provided, accessed via `Control/(control Type)`. If this is not
available, `Control/ControlBase` is loaded.

Arguments:
- `type`: Type of control
- `attribute`: The PIM attribute for this control
- `variables`: Additional variables for the control partial
- `isMultiValued`: If this control offers multiple values (default false)

#### mS3:AjaxSearch.Result
Represents the result panel of an AJAX search.

The search form will by default issue an AJAX request after loading to get initial results.
However, if the setting `initializeStaticResult` is set to 1, the rendering of initial results is done on server-side.

Arguments:
- `resultTemplate`: The Fluid template file name for the result
- `root`: The root PIM object for accessing initial values
- `variables`: Additional variables for the control partial
- `start`: Start index for pagination of initial results

## Additional features
### Speaking URLs
mS3 Commerce FX can provide speaking URLs for product and group pages.

This requires preparation on the PIM side (the Feature Pivot Table Generator -> RealURL in mS3 Commerce), where
the URLs can be configured.

To enable speaking URLs, the mS3 Commerce FX Extension setting `link.byGuid` must be set to 1.
Additionally, the following routeEnhancer must be added to the Typo3 Site config:

    routeEnhancers:
      mS3CommerceFx:
        type: Plugin
        routePath: '/{rootGuid}'
        namespace: tx_ms3commercefx_pi1
        requirements:
          rootGuid: '.*'
        aspects:
          rootGuid:
            type: Ms3CommerceFxRoutingMapper
        (limitToPages as required)

### Full Text Search
mS3 Commerce FX can offer a full text search for products.

This requires preparation on the PIM side (the Fulltext Finisher in mS3 Commerce), where the searchable attributes
and weights are configured.  
In dataTransfer, the `MS3C_SEARCH_BACKEND` must be enabled (only `MySQL` is currently supported).

This enables search and the use of the Search Result frontend Plugin as described above.

#### Search Result display
Search will always find Products (i.e. lowest leven in PIM). It is possible however to consolidate the search result
onto a given Structure Element, e.g. find "Article", but return "Product Group" objects.

The following settings exist for the `plugin.tx_ms3commercefx.settings.fulltextSearch`:
- `pageSize`: Number of results per page
- `resultStructureElement`: Structure Element name for consolidating results

### Ajax Filters
Ajax Filters will always find Products (i.e. lowest leven in PIM). It is possible however to consolidate the search result
onto a given Structure Element, e.g. find "Article", but return "Product Group" objects.

The following settings exist for the `plugin.tx_ms3commercefx.settings.ajaxSearch`:
- `pageSize`: Number of results per page
- `resultStructureElement`: Structure Element name for consolidating results
- `resultTemplate`: Template file for result layout
- `sortFilterValues`: If the available values for filters should be sorted.
  - (not given): Don't sort filter values 
  - `natural`: uses natural sorting (cf. [strnatcasecmp](https://www.php.net/manual/en/function.strnatcasecmp.php))
  - `custom_(mySortFunction)`: uses the custom function `mySortFunction` for sorting (any value possible)

### User and market based restrictions
mS3 Commerce FX can automatically exclude certain Products/Groups based on the current site (=Market Restriction),
or the currently logged in Typo3 User (=User Restriction), or a combination thereof.

The restriction is based on a simple label based control, e.g. a set of Product labels are compared to a set
of Market / User labels. If one label matches, the product is visible.

For this, a PIM Attribute must be configured as the Market and/or User restriction attribute. This attribute can
contain `;`-separated values.
This attribute can be set globally (takes inheritance into account, as described above), or set independently for
single Structure Elements.

The "allowed" Market values are configured in TypoScript.

The "allowed" User values are defined in the Typo3 FE User records in the Typo3 Backend. The FE User's labels
are combined with all the User's FE Groups' labels.

Parameters in the `plugin.tx_ms3commercefx.settings.marketRestriction`:
- `attribute`: The attribute for market restriction
- `values`: The allowed values for this site
- `levels`: Structure Element specific values:
  - `name`: Name of the Structure Element
  - `attribute`: The attribute
  - `values`: The allowed values

Parameters in the `plugin.tx_ms3commercefx.settings.userRestriction`:
- `attribute`: The attribute for user restriction
- `notLoggedInValues`: The values for not logged-in users
- `defaultValues`: The values every user has by default (added to user values and not logged-in values)

### cart Integration
mS3 Commerce FX has an integration for [Cart](https://extensions.typo3.org/extension/cart), so that PIM products
can be handled by this extension.

To enable this, the dataTransfer parameter `MS3C_SHOP_SYSTEM` must be set to `tx_cart`

The cart integration gives additional View Helpers to ease integration:

#### mS3:Shop.CartForm
This view helper can be used in mS3 Commerce templates (e.g. List or Detail view of the plugin), to build a form
to add a product to cart.

Arguments:
- `basketPid`: The cart basket PID
- `ajax`: If product should be added via AJAX (default false)
- `useDefaultResponses`: If default responses for AJAX success/error should be generated (true), or custom messages are povided (false = default).
  If this is false, the template should contain an element with class `form-message`, containing elements with classes
  `form-success` and `form-error`, respectively

Inside the view helper, additional variables are available in the template:
- `productFieldName`: The field name for a `<input>` field that must contain the product Id
- `quantityFieldName`: The field name for a `<input>` field that must contain the quantity

Example:

    <ms3:Shop.CartForm basketPid="123" 
                       ajax="1"
                       useDefaultResponses="1"
    >
      <span class="productSKU">Product: {product.name}</span>
      <input type="text" name="{quantityFieldName}" value="{product.basketQuantity}"/>
      <input type="hidden" name="{productFieldName}" value="{product.id}"/>
    </mS3:Shop.CartForm>

#### mS3:Shop.PimProduct
This view helper can be used in Cart templates, to access the PIM Product for a product in Cart.
It is useful to access additional information e.g. in Cart's basket templates.

Arguments:
- `product`: The Cart product
- `as`: The name for the variable as which the PIM product can be accessed (default `pimProduct`)

Example: (inside Cart Basket Item Template)

    <mS3:Shop.PimProduct product="{product}" as="pp">
        {pp.attributes.Detail_Description.ContentHtml}
    </mS3:Shop.PimProduct>

## Detailed Configuration
### Plugin settings
All parameters in `plugin.tx_ms3commercefx.settings`:

- `rootId`: The start element Id if no item is selected (via parameters). These IDs are not stable, prefer `rootGuid`
- `rootGuid`: THe start element Guid if no item is selected (via parameters). Can be including Shop Id (e.g. `XXX:1`).  
   Note that the entry point can be overridden in the Plugin Settings.  
- `shopId`: The Shop Id. This is required if rootGuid is used without Shop Id
- `startId`: The Start Id for the Menu. By default, this is the root object, but can be different. These IDs are not stable, prefer `startGuid`
- `startGuid`: The Start Guid for the Menu. By default, this is the root object, but can be different.
- `templateFile`: The template file to use. This value can be overridden in the Plugin Settings
- `link`: Parameters for link generation
  - `pid`: Default PID for Links (if not set: current page).
  - `byGuid`: Create links by GUID (required for [Speaking URLs](#speaking-urls)

- `ajaxSearch`: Parameters for [Ajax Search](#ajax-filters)
- `fullTextSearch`: Parameters for [Full Text Search](#full-text-search)

- `numberFormat`: Number format to apply when displaying numbers, e.g. Prices
  - `comma`: The comma character
  - `thousands`: The thousands separator
- `notFoundMode`: How the plugin reacts if the given object is not found (or exlucded due to restrictions)
  - (no value): Just display template without object
  - `404`: Use Typo3 default 404 message
- `includeUsageTypes`: PIM Object Usage Types to display. The default configuration will exclude special objects, this can be changed here
- `marketRestriction`: Parameters for [Market restriction](#user-and-market-based-restrictions)
- `userRestriction`: Parameters for [User restriction](#user-and-market-based-restrictions)
- `priceMarket`: The "Market" parameter for prices and availability.
- `tx_cart`: Additional parameters for Cart integration
  - `basketPid`: The Cart Basket PID
  - `addBasketMode`: How products are added to the basket
    - (not set): Product Quantity will add
    - `replace`: Product Quantity will be replaced
- `ItemSelector`  
  Configuration for selecting items as entry point in Backend
  - `StructureElement`: The default Structure Element
  - `ShopId`: The Shop-ID
  - `withParent`: If the parent elements should be displayed in the selection

### Important dataTransfer parameters
- `MS3C_CMS_TYPE`: Must be `Typo3`
- `MS3C_TYPO3_TYPE`: Must be `FX`
- `MS3C_TYPO3_CACHED`: Whether the plugin results are cached in the Typo3 Caching framework (see [Caching](#caching))
- `MS3C_SHOP_SYSTEM`: Must be `tx_cart` if Cart integration is used
- `MS3C_SEARCH_BACKEND`: Must be `MySQL` if Full Text Search is used

## Technical details
### Lazy bulk loading

The mS3 Commerce FX Extension tries to minimize SQL loads. 

It will load many things only on request (lazy loading), e.g. Attributes, Children, Categorizations, etc.
However, when some property is requested, it will load all relevant values for the current object, 
_and all objects it was loaded with_ (bulk loading). 

E.g. if `{object.children}` is called, the basic information of all children are loaded. If then the `attributes` 
of one of these children is accessed, _all_ attributes of _all_ children are loaded at once. 

This is done, as most of the time, templates will loop over the children and do the same thing.

However, if only the 1st child is really needed, the `{object.firstChild}` is more performant, as it will not bulk
load other children.

## PHP Interface
### Models
The properties described above for Fluid Templating can be used the same way in PHP using the Model classes in the
`Ms3\Ms3CommerceFx\Domain\Model` namespace. Note that the properties will be accessed by getters, so `{object.attributes}`
becomes `$object->getAttributes()`.

### Repository
The easiest way to interface mS3 PIM data in your own extension is to inject `\Ms3\Ms3CommerceFx\Domain\Repository\RepositoryFacade`.
It provides the most common functions for loading objects, and others.

It must be bootstrapped with `QuerySettings`, that can be initialized by `QuerySettings::fromSettings`, which takes a
parameter as the descirebed above in [plugin settings](#plugin-settings). Most important property is `ShopId` (or other
means to determine the Shop, e.g. `rootId` or `rootGuid` with shopId added).

### Controllers and actions
#### ObjectController
This is the controller for the List and Detail plugin views, and has according actions `listAction` and `detailAction`

#### MenuController
This is the controller for the Menu integration, and offers the action `menuAction`

#### SearchController
This is the controller for Fulltext Search, with `searchAction`

#### AjaxSearchController
This is the controller for dynamic filters, with `filterAction`

### Hooks
The following services can be overridden using the Typo3 XCLASS method (cf [XCLASSes](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Xclasses/Index.html))

- `ObjectCreationService`: Responsible for creating PIM Objects. Override this to use custom drop in classes
- `LinkService`: Responsible for creating Links. Override this for special link handling
- `RestrictionService`: Responsible for object visibility (restrictions). Override to apply custom restriction rules
- `AddToCartFinisherListener`: Responsible to create Cart products from request to add to Basket. 
   Override for custom handling of parameters (e.g. additional VPE, multiple products per request, ...), or 
   additional information for products (e.g. tax classes, etc) 
