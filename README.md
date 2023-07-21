# Sitegeist.Noderobis
## Cli-kickstarter for Neos CMS - NodeTypes

### Authors & Sponsors

* Melanie Wüst - wuest@sitegeist.de
* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored
by our employer http://www.sitegeist.de.*

## Installation

Sitegeist.Noderobis is available via packagist and can be installed with the command `composer require sitegeist/noderobis`.

We use semantic-versioning so every breaking change will increase the major-version number.

## Usage

The package offers cli commands to define new nodetypes. The commands will create a `NodeTypes/*.yaml` file and 
a `NodeTypes/*.fusion` renderer (for non abstract nodetypes) that renders all properties and children via afx.

```
PACKAGE "SITEGEIST.NODEROBIS":
-------------------------------------------------------------------------------
  kickstart:document                       
  kickstart:content                        
  kickstart:mixin                          
```

### Commands to create Document|Content|Mixin NodeTypes 


```
./flow kickstart:document [<options>] <name>
./flow kickstart:content [<options>] <name>
./flow kickstart:mixin [<options>] <name>

ARGUMENTS:
  --name               Node Name, last part of NodeType

OPTIONS:
  --package-key        (optional) Package, uses fallback from configuration
  --mixin              (optional) Mixin-types to add as SuperTypes, can be used
                       multiple times
  --child-node         (optional) childNode-names and childNode-NodeType
                       seperated by a colon, can be used multiple times
  --property           (optional) property-names and property-NodeType
                       seperated by a colon, can be used multiple times
  --abstract           (optional) By default contents and documents are created
                       non abstract
  --yes                (optional) Skip refinement-process and apply all
                       modifications directly
```

### Specification refinement 

The create commands will call an interactive refinement process after the initial specification is created. This will
allow to specify additional properties, mixins etc. as it would be very tedious to put all this into a single cli-call.

```
Vendor.Example:Document.Article
  SuperTypes: Neos.Neos:Document

What is next?
  [0] FINISH and generate files
  [1] add Label
  [2] add Icon
  [3] add Property
  [4] add ChildNode
  [5] add SuperType
  [6] add Mixin
  [7] make Abstract
  [8] exit
 > 
```

The refinement shows a summary of the specification so far and offers to make adjustments. Once you are satisfied you
choose "FINISH and generate files" to trigger the generation process.

## Configuration

The package allows to configure defaults for packageKeys, superTypes and the generated fusion code for each property type.

```yaml
Sitegeist:
  Noderobis:
    # package key to be used by default if none is specified
    defaultPackageKey: ~

    # default supertypes for nodetypes when no supertype is found in the package namespace
    superTypeDefaults:
      Document: Neos.Neos:Document
      Content: Neos.Neos:Content

    # modification generators that will be applied
    modificationGenerators:
      createNodeTypeYamlFile: '\Sitegeist\Noderobis\Domain\Generator\CreateNodeTypeYamlFileModificationGenerator'
      createFusionRenderer: '\Sitegeist\Noderobis\Domain\Generator\CreateFusionRendererModificationGenerator'
      includeFusionFromNodeTypes: '\Sitegeist\Noderobis\Domain\Generator\IncludeFusionFromNodeTypesModificationGenerator'
      
    # configuration for accessing and rendering properties in fusion the key `default` is used if no special
    # config is found for a type
    properties:
      'default':
        prop: '###NAME### = ${q(node).property("###NAME###")}'
        afx: '{String.htmlSpecialChars(Json.serialize(props.###NAME###))}'
      'inlineEditable':
        afx: '<Neos.Neos:Editable property="###NAME###" />'
      'Neos\Media\Domain\Model\ImageInterface':
        afx: '<Neos.Neos:ImageTag asset={props.###NAME###} preset="Neos.Media.Browser:Thumbnail" />'
      'Neos\Media\Domain\Model\Asset':
        afx: '<Neos.Fusion:Link.Resource href.resource={props.###NAME###.resource} >{props.###NAME###.label}</Neos.Fusion:Link.Resource>'
      'array<Neos\Media\Domain\Model\Asset>':
        afx: '<ul><Neos.Fusion:Loop items={props.###NAME###} itemName="asset"><li><Neos.Fusion:Link.Resource href.resource={asset.resource} >{asset.label}</Neos.Fusion:Link.Resource></li></Neos.Fusion:Loop></ul>'
      'DateTime':
        afx: '{Date.format(props.###NAME### , "Y-m-d")}'
      'reference':
        afx: '<Neos.Neos:NodeLink node={props.###NAME###} >{props.###NAME###.label}</Neos.Neos:NodeLink>'
      'references':
        afx: '<ul><Neos.Fusion:Loop items={props.###NAME###} itemName="reference"><li><Neos.Neos:NodeLink node={reference} >{reference.label}</Neos.Neos:NodeLink></li></Neos.Fusion:Loop></ul>'
      'string':
        afx: '{props.###NAME###}'
      'integer':
        afx: '{props.###NAME###}'
      'boolean':
        afx: '{props.###NAME### ? "true" : "false"}'
```

### Configuration to support Sitegeist.Kaleidoscope

The following Settings.yaml adjusts the generated code for `Neos\Media\Domain\Model\ImageInterface` props
to use the [Sitegeist.Kaleidoscope](https://github.com/sitegeist/Sitegeist.Kaleidoscope) package.

```yaml
Sitegeist:
  Noderobis:
    properties:

      # Adjust generation of Image props to render using Sitegeist.Kaleidoscope
      # with fallback to a dummy image in backend
      'Neos\Media\Domain\Model\ImageInterface':
        prop: |
          ###NAME### = Neos.Fusion:Case {
              image {
                  condition = ${q(node).property('###NAME###')}
                  renderer = Sitegeist.Kaleidoscope:AssetImageSource {
                      asset = ${q(node).property('###NAME###')}
                      # @todo !!! ensure property ###NAME###Title really exists
                      title = ${q(node).property('###NAME###Title')}
                      # @todo !!! ensure property ###NAME###Alt really exists
                      alt = ${q(node).property('###NAME###Alt')}
                  }
              }
              dummyImage {
                  condition = ${node.context.inBackend}
                  renderer = Sitegeist.Kaleidoscope:DummyImageSource {
                      alt = "dummy"
                      title = "image"
                  }
              }
          }
        afx: '<Sitegeist.Kaleidoscope:Image imageSource={props.###NAME###} />'
```

## How it works

The package will firstly use the cli process to generate a `NodeTypeSpecification` value-object. 
This specification is used to create a `NodeType` object. This object is then used to generate
the needed `NodeTypes/*.yaml` file and a `NodeTypes/*.fusion` file for rendering.

## Contribution

We will gladly accept contributions. Please send us pull requests.
