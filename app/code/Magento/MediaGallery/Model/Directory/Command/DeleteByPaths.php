<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MediaGallery\Model\Directory\Command;

use Magento\Cms\Model\Wysiwyg\Images\Storage;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\MediaGalleryApi\Api\DeleteDirectoriesByPathsInterface;
use Magento\MediaGalleryApi\Api\IsPathBlacklistedInterface;
use Psr\Log\LoggerInterface;

/**
 * Delete directory by provided paths in the media storage
 */
class DeleteByPaths implements DeleteDirectoriesByPathsInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var IsPathBlacklistedInterface
     */
    private $isPathBlacklisted;

    /**
     * @param LoggerInterface $logger
     * @param Storage $storage
     * @param IsPathBlacklistedInterface $isPathBlacklisted
     */
    public function __construct(
        LoggerInterface $logger,
        Storage $storage,
        IsPathBlacklistedInterface $isPathBlacklisted
    ) {
        $this->logger = $logger;
        $this->storage = $storage;
        $this->isPathBlacklisted = $isPathBlacklisted;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $paths): void
    {
        $failedPaths = [];
        foreach ($paths as $path) {
            if ($this->isPathBlacklisted->execute($path)) {
                $failedPaths[] = $path;
                continue;
            }
            try {
                $this->storage->deleteDirectory($this->storage->getCmsWysiwygImages()->getStorageRoot() . $path);
            } catch (\Exception $exception) {
                $this->logger->critical($exception);
                $failedPaths[] = $path;
            }
        }

        if (!empty($failedPaths)) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete directories: %paths',
                    [
                        'paths' => implode(' ,', $failedPaths)
                    ]
                )
            );
        }
    }
}
