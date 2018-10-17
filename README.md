# SonataMediaLiipImagineBridgeBundle

This bundle is a bridge to use LiipImagineBundle with SonataMediaBundle.

## Installation

`composer req umanit/sonata-media-liip-imagine-bridge-bundle`

## Usage

Two twig filters are provided:

**image**

Use it to display a full img tag.

```HTML
{{ object.media|image({width: 350, height: 200, alt: object.title, class: 'my-fancy-class'}, 'outbound') }}
```

You must at least provide the options `width` and `height`.

**media_path**

Use this one to only display the path of an image.

```HTML
<img src="{{ object.media_path|image(350, 200, 'outbound' }}">
```
