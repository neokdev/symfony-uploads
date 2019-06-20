<?php

namespace App\Service;

use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const ARTICLE_IMAGE = 'article_image';
    const ARTICLE_REFERENCE = 'article_reference';
    /**
     * @var RequestStackContext
     */
    private $context;
    /**
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $publicAssetsBaseUrl;
    /**
     * @var FilesystemInterface
     */
    private $privateFilesystem;

    public function __construct(
        FilesystemInterface $publicUploadFilesystem,
        FilesystemInterface $privateUploadFilesystem,
        RequestStackContext $context,
        LoggerInterface $logger,
        string $uploadedAssetsBaseUrl
    ) {
        $this->context = $context;
        $this->filesystem = $publicUploadFilesystem;
        $this->logger = $logger;
        $this->publicAssetsBaseUrl = $uploadedAssetsBaseUrl;
        $this->privateFilesystem = $privateUploadFilesystem;
    }

    /**
     * @param File        $file
     * @param string|null $existingFilename
     *
     * @return string
     *
     * @throws Exception
     */
    public function uploadArticleImage(
        File $file,
        ?string $existingFilename
    ): string {
        $newFilename = $this->uploadFile($file, self::ARTICLE_IMAGE, true);

        if ($existingFilename) {
            try {
                $result = $this->filesystem->delete(self::ARTICLE_IMAGE.'/'.$existingFilename);

                if (false === $result) {
                    throw new Exception(sprintf('Could not delete old uploaded file "%s"', $existingFilename));
                }
            } catch (FileNotFoundException $e) {
                $this->logger->alert(sprintf('Old uploaded file "%s" was missing when trying to delete', $existingFilename));
            }
        }

        return $newFilename;
    }

    /**
     * @param File $file
     *
     * @return string
     *
     * @throws Exception
     */
    public function uploadArticleReference(File $file): string
    {
        return $this->uploadFile($file, self::ARTICLE_REFERENCE, false);
    }

    public function getPublicPath(string $path): string
    {
        return $this->context->getBasePath().$this->publicAssetsBaseUrl.'/'.$path;
    }

    /**
     * @param File   $file
     * @param string $directory
     * @param bool   $isPublic
     *
     * @return string
     *
     * @throws FileExistsException
     */
    private function uploadFile(File $file, string $directory, bool $isPublic): string
    {
        if ($file instanceof UploadedFile) {
            /** @var UploadedFile $file */
            $originalFilename = $file->getClientOriginalName();
        } else {
            /** @var File $file */
            $originalFilename = $file->getFilename();
        }

        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME).'-'.uniqid().'.'.$file->guessExtension());

        $filesystem = $isPublic ? $this->filesystem : $this->privateFilesystem;

        $stream = fopen($file->getPathname(), 'r');
        $result = $filesystem->writeStream(
            $directory.'/'.$newFilename,
            $stream
        );

        if (false === $result) {
            throw new Exception(sprintf('Could not write uploaded file "%s"', $newFilename));
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $newFilename;
    }
}
