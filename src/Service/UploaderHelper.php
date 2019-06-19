<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const ARTICLE_IMAGE = 'article_image';
    /**
     * @var string
     */
    private $uploadsPath;
    /**
     * @var RequestStackContext
     */
    private $context;

    public function __construct(
        string $uploadsPath,
        RequestStackContext $context
    ) {
        $this->uploadsPath = $uploadsPath;
        $this->context = $context;
    }

    public function uploadArticleImage(File $file): string
    {
        $destination = $this->uploadsPath.'/'.self::ARTICLE_IMAGE;

        if ($file instanceof UploadedFile) {
            /** @var UploadedFile $file */
            $originalFilename = $file->getClientOriginalName();
        } else {
            /** @var File $file */
            $originalFilename = $file->getFilename();
        }

        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME).'-'.uniqid().'.'.$file->guessExtension());

        $file->move(
            $destination,
            $newFilename
        );

        return $newFilename;
    }

    public function getPublicPath(string $path): string
    {
        return $this->context->getBasePath().'/uploads/'.$path;
    }
}
