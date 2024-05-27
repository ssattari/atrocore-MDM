<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Atro\Repositories;

use Atro\Core\Exceptions\BadRequest;
use Atro\Core\Exceptions\NotUnique;
use Atro\Core\FileStorage\FileStorageInterface;
use Atro\Core\FileStorage\LocalFileStorageInterface;
use Atro\Core\FileValidator;
use Atro\Entities\File as FileEntity;
use Atro\Core\Templates\Repositories\Base;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Espo\Core\FilePathBuilder;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;

class File extends Base
{
    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        $this->prepareThumbnailsPath($entity);

        // validate via type
        $this->validateByType($entity);

        // validate file name
        $this->validateItemName($entity);

        if (!$entity->isNew()) {
            if ($entity->isAttributeChanged('storageId')) {
                throw new BadRequest($this->getInjection('language')->translate('fileStorageCannotBeChanged', 'exceptions', 'File'));
            }

            if ($entity->isAttributeChanged('folderId')) {
                $storageId = $this->getEntityManager()->getRepository('Folder')->getFolderStorage($entity->get('folderId') ?? '')->get('id');
                if ($storageId !== $entity->get('storageId')) {
                    throw new BadRequest($this->getInjection('language')->translate('fileCannotBeMovedToAnotherStorage', 'exceptions', 'File'));
                }
            }

            if (!empty($entity->_input) && !empty($entity->_input->reupload)) {
                if ($entity->isAttributeChanged('folderId')) {
                    throw new BadRequest($this->getInjection('language')->translate('fileFolderCannotBeChanged', 'exceptions', 'File'));
                }
                // recreate origin file
                if (!$this->getStorage($entity)->reupload($entity)) {
                    throw new BadRequest($this->getInjection('language')->translate('fileCreateFailed', 'exceptions', 'File'));
                }
            } else {
                if ($entity->isAttributeChanged('name') || $entity->isAttributeChanged('folderId')) {
                    $this->updateItem($entity);
                }

                if ($entity->isAttributeChanged('name')) {
                    $this->rename($entity);
                }
            }
        } else {
            // assign the file type automatically
            if (empty($entity->get('typeId'))) {
                $fileTypes = $this->getEntityManager()->getRepository('FileType')
                    ->where(['assignAutomatically' => true])
                    ->order('priority', 'DESC')
                    ->find();
                foreach ($fileTypes as $fileType) {
                    if ($this->getFileValidator()->validateFile($fileType, $entity)) {
                        $entity->set('typeId', $fileType->get('id'));
                        break;
                    }
                }
            }

            $this->createItem($entity);

            // create origin file
            if (empty($options['scanning']) && !$this->getStorage($entity)->create($entity)) {
                throw new BadRequest($this->getInjection('language')->translate('fileCreateFailed', 'exceptions', 'File'));
            }
        }
    }

    public function prepareThumbnailsPath(FileEntity $file): void
    {
        if (empty($file->get('thumbnailsPath'))) {
            if (!empty($file->get('path'))) {
                $file->set('thumbnailsPath', $file->get('path'));
            } else {
                $thumbnailsDirPath = trim($this->getConfig()->get('thumbnailsPath', 'upload/thumbnails'), '/');
                $file->set('thumbnailsPath', $this->getPathBuilder()->createPath($thumbnailsDirPath . DIRECTORY_SEPARATOR));
            }
        }
    }

    public function save(Entity $entity, array $options = [])
    {
        $inTransaction = $this->getPDO()->inTransaction();

        if (!$inTransaction) {
            $this->getPDO()->beginTransaction();
        }

        try {
            $res = parent::save($entity, $options);
        } catch (\Throwable $e) {
            if ($inTransaction) {
                $this->getPDO()->rollBack();
            }
            throw $e;
        }

        if ($inTransaction) {
            $this->getPDO()->commit();
        }

        return $res;
    }

    protected function deleteEntity(Entity $entity): bool
    {
        $inTransaction = $this->getPDO()->inTransaction();

        if (!$inTransaction) {
            $this->getPDO()->beginTransaction();
        }

        try {
            $res = parent::deleteEntity($entity);
            if ($res) {
                $this->removeItem($entity);
            }
        } catch (\Throwable $e) {
            if ($inTransaction) {
                $this->getPDO()->rollBack();
            }
            throw $e;
        }

        if ($inTransaction) {
            $this->getPDO()->commit();
        }

        return $res;
    }

    public function rename(FileEntity $file): void
    {
        if ($this->isExtensionChanged($file)) {
            throw new BadRequest($this->getInjection('language')->translate('fileExtensionCannotBeChanged', 'exceptions', 'File'));
        }

        if (!$this->isNameValid($file)) {
            throw new BadRequest(
                sprintf($this->getInjection('language')->translate('fileNameNotValidByUserRegex', 'exceptions', 'File'), $this->getConfig()->get('fileNameRegexPattern'))
            );
        }

        if (!$this->getStorage($file)->rename($file)) {
            throw new BadRequest($this->getInjection('language')->translate('fileRenameFailed', 'exceptions', 'File'));
        }
    }

    public function validateByType(FileEntity $file): void
    {
        if (!empty($file->get('typeId'))) {
            $fileType = $this->getEntityManager()->getRepository('FileType')->get($file->get('typeId'));
            $this->getFileValidator()->validateFile($fileType, $file, true);
        }
    }

    public function isNameValid(FileEntity $file): bool
    {
        $fileNameRegexPattern = $this->getConfig()->get('fileNameRegexPattern');
        if (!empty($fileNameRegexPattern)) {
            $nameWithoutExt = explode('.', (string)$file->get('name'));
            array_pop($nameWithoutExt);
            $nameWithoutExt = implode('.', $nameWithoutExt);
            return preg_match($fileNameRegexPattern, $nameWithoutExt);
        }

        return true;
    }

    public function isExtensionChanged(FileEntity $file): bool
    {
        $fetchedParts = explode('.', (string)$file->getFetched('name'));
        $fetchedExt = array_pop($fetchedParts);

        $parts = explode('.', (string)$file->get('name'));
        $ext = array_pop($parts);

        return $fetchedExt !== $ext;
    }

    protected function beforeRemove(Entity $entity, array $options = [])
    {
        parent::beforeRemove($entity, $options);

        if (empty($options['keepFile'])) {
            $this->deleteFile($entity);
        }
    }

    public function deleteFile(FileEntity $entity): void
    {
        // delete origin file
        if (!$this->getStorage($entity)->delete($entity)) {
            throw new BadRequest($this->getInjection('language')->translate('fileDeleteFailed', 'exceptions', 'File'));
        }
    }

    public function getContents(FileEntity $file): string
    {
        return $this->getStorage($file)->getContents($file);
    }

    public function getFilePath(FileEntity $file): string
    {
        $fileStorage = $this->getStorage($file);

        if ($fileStorage instanceof LocalFileStorageInterface) {
            return $fileStorage->getLocalPath($file);
        }

        return $fileStorage->getUrl($file);
    }

    public function getPathsData(FileEntity $file): array
    {
        return [
            'download'   => $this->getDownloadUrl($file),
            'thumbnails' => [
                'small'  => $this->getSmallThumbnailUrl($file),
                'medium' => $this->getMediumThumbnailUrl($file),
                'large'  => $this->getLargeThumbnailUrl($file)
            ],
        ];
    }

    public function createItem(Entity $entity): void
    {
        $qb = $this->getConnection()->createQueryBuilder()
            ->insert('file_folder_linker')
            ->setValue('id', ':id')
            ->setValue('name', ':name')
            ->setValue('parent_id', ':parentId')
            ->setValue('file_id', ':fileId')
            ->setParameter('id', Util::generateId())
            ->setParameter('name', $entity->get('name'))
            ->setParameter('parentId', $entity->get('folderId') ?? '')
            ->setParameter('fileId', $entity->get('id'));
        try {
            $qb->executeQuery();
        } catch (UniqueConstraintViolationException $e) {
            throw new NotUnique($this->getInjection('language')->translate('suchItemNameCannotBeUsedHere', 'exceptions'));
        }
    }

    public function updateItem(Entity $entity): void
    {
        $qb = $this->getConnection()->createQueryBuilder()
            ->update('file_folder_linker')
            ->set('name', ':name')
            ->set('parent_id', ':parentId')
            ->where('file_id=:fileId')
            ->setParameter('name', $entity->get('name'))
            ->setParameter('parentId', $entity->get('folderId') ?? '')
            ->setParameter('fileId', $entity->get('id'));
        try {
            $qb->executeQuery();
        } catch (UniqueConstraintViolationException $e) {
            throw new NotUnique($this->getInjection('language')->translate('suchItemNameCannotBeUsedHere', 'exceptions'));
        }
    }

    public function removeItem(Entity $entity): void
    {
        $this->getConnection()->createQueryBuilder()
            ->delete('file_folder_linker')
            ->where('file_id=:fileId')
            ->setParameter('fileId', $entity->get('id'))
            ->executeQuery();
    }

    public function validateItemName(FileEntity $file): void
    {
        if ($file->isNew() || $file->isAttributeChanged('name') || $file->isAttributeChanged('folderId')) {
            $qb = $this->getConnection()->createQueryBuilder()
                ->select('*')
                ->from('file_folder_linker')
                ->where('name=:name')
                ->andWhere('parent_id=:parentId')
                ->setParameter('name', $file->get('name'))
                ->setParameter('parentId', $file->get('folderId') ?? '');

            if (!$file->isNew()) {
                $qb->andWhere('id!=:id')->setParameter('id', $file->get('id'));
            }

            if (!empty($qb->fetchAssociative())) {
                throw new NotUnique($this->getInjection('language')->translate('suchItemNameCannotBeUsedHere', 'exceptions'));
            }
        }
    }

    public function getDownloadUrl(FileEntity $file): string
    {
        return $this->getStorage($file)->getUrl($file);
    }

    public function getSmallThumbnailUrl(FileEntity $file): ?string
    {
        return $this->getStorage($file)->getThumbnail($file, 'small');
    }

    public function getMediumThumbnailUrl(FileEntity $file): ?string
    {
        return $this->getStorage($file)->getThumbnail($file, 'medium');
    }

    public function getLargeThumbnailUrl(FileEntity $file): ?string
    {
        return $this->getStorage($file)->getThumbnail($file, 'large');
    }

    public function getStorage(FileEntity $file): FileStorageInterface
    {
        return $this->getInjection('container')->get($file->get('storage')->get('type') . 'Storage');
    }

    protected function getPathBuilder(): FilePathBuilder
    {
        return $this->getInjection('container')->get('filePathBuilder');
    }

    protected function getFileValidator(): FileValidator
    {
        return $this->getInjection('container')->get(FileValidator::class);
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('container');
        $this->addDependency('language');
        $this->addDependency('fileValidator');
    }
}
