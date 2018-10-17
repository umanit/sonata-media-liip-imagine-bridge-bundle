<?php

namespace Umanit\SonataMediaLiipImagineBridgeBundle\Twig\Extension;

use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Service\FilterService;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\MediaProviderInterface;
use Sonata\MediaBundle\Provider\Pool as MediaProviderPool;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\TwigFilter;

/**
 * @author Arthur Guigand <aguigand@umanit.fr>
 */
class ImageExtension extends \Twig_Extension
{
    // @todo AGU : Make this configurable
    public const DEFAULT_MEDIA = '/assets/placeholder.jpg';

    /** @var MediaProviderPool */
    protected $mediaProviderPool;

    /** @var CacheManager */
    protected $cacheManager;

    /**
     * Constructor.
     *
     * @param MediaProviderPool $mediaProviderPool
     * @param CacheManager      $cacheManager Cache manager Liip
     */
    public function __construct(MediaProviderPool $mediaProviderPool, CacheManager $cacheManager)
    {
        $this->mediaProviderPool = $mediaProviderPool;
        $this->cacheManager      = $cacheManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('media_path', [$this, 'getMediaPath']),
            new TwigFilter('image', [$this, 'image'], ['is_safe_callback' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * Displays a media img tag. X and Y are doubled for retina displays.
     *
     * @param MediaInterface|null $media The Media to be displayed.
     * @param array               $attr  The img attributes. 'width', 'height', 'class', 'alt'.
     * @param string              $mode  The display mode ('outbound' or 'inset')
     *
     * @return string
     */
    public function image(MediaInterface $media, array $attr = [], $mode = 'outbound'): string
    {
        $this->validateMode($mode);

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'width'  => 200,
            'height' => 200,
            'class'  => null,
            'alt'    => null,
        ]);

        $resolver->setAllowedTypes('width', ['int']);
        $resolver->setAllowedTypes('height', ['int']);
        $resolver->setAllowedTypes('alt', ['string', 'null']);
        $resolver->setAllowedTypes('class', ['string', 'null']);

        $attr = $resolver->resolve($attr);

        return strtr('<img src="%src%" alt="%alt%" width="%width%" height="%height%"%class%>', [
            '%src%'    => $this->getMediaPath($media, $attr['width'] * 2, $attr['height'] * 2, $mode),
            '%alt%'    => $attr['alt'] ?? $media ?? 'placeholder.jpg',
            '%width%'  => $attr['width'],
            '%height%' => $attr['height'],
            '%class%'  => $attr['class'] ? ' class="'.$attr['class'].'"' : '',
        ]);
    }

    /**
     * Get the path of a media.
     *
     * @param MediaInterface|null $media
     * @param int                 $width
     * @param int                 $height
     * @param string              $mode mode de redimensionnement
     *
     * @return string
     */
    public function getMediaPath(MediaInterface $media = null, int $width, int $height, $mode = ImageInterface::THUMBNAIL_OUTBOUND): string
    {
        $this->validateMode($mode);

        $referencePicture = self::DEFAULT_MEDIA;

        if (null !== $media) {
            $mediaProvider = $this->mediaProviderPool->getProvider($media->getProviderName());

            $referencePicture = $mediaProvider->generatePublicUrl(
                $media,
                $mediaProvider->getFormatName($media, MediaProviderInterface::FORMAT_REFERENCE)
            );
        }

        return $this->cacheManager->getBrowserPath($referencePicture, 'default', [
            'thumbnail' => [
                'size' => [$width, $height],
                'mode' => $mode,
            ],
        ]);
    }

    /**
     * Validates the cropping mode.
     *
     * @param string $mode
     */
    private function validateMode(string $mode)
    {
        if (!\in_array($mode, [ImageInterface::THUMBNAIL_INSET, ImageInterface::THUMBNAIL_OUTBOUND], true)) {
            throw new \LogicException(
                strtr('Unknown mode \'%mode%\'. The display mode can only be one of \'inset\' or \'outbound\'', [
                    '%mode%' => $mode,
                ])
            );
        }
    }
}
