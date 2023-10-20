<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Atro\Core\Templates\Repositories;

use Atro\ORM\DB\RDB\Mapper;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\Repositories\RDB;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\ORM\EntityFactory;
use Espo\ORM\EntityManager;

class Hierarchy extends RDB
{
    protected string $tableName;
    protected string $hierarchyTableName;

    public function __construct($entityType, EntityManager $entityManager, EntityFactory $entityFactory)
    {
        parent::__construct($entityType, $entityManager, $entityFactory);

        $this->tableName = $entityManager->getMapper()->toDb($this->entityType);
        $this->hierarchyTableName = $this->tableName . '_hierarchy';
    }

    public function findRelated(Entity $entity, $relationName, array $params = [])
    {
        if ($relationName === 'children') {
            $params['orderBy'] = $this->hierarchyTableName . '.hierarchy_sort_order';
        }

        return parent::findRelated($entity, $relationName, $params);
    }

    public function getEntityPosition(Entity $entity, string $parentId): ?int
    {
        echo '<pre>';
        print_r('getEntityPosition');
        die();

//        $sortBy = Util::toUnderScore($this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'sortBy'], 'name'));
//        $sortOrder = !empty($this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'asc'])) ? 'ASC' : 'DESC';
//
//        $id = $this->getPDO()->quote($entity->get('id'));
//
//        if (empty($parentId)) {
//            $query = "SELECT x.position
//                      FROM (SELECT t.id, @rownum:=@rownum + 1 AS position
//                            FROM `$this->tableName` t
//                                JOIN (SELECT @rownum:=0) r
//                                LEFT JOIN `$this->hierarchyTableName` h ON t.id=h.entity_id AND h.deleted=0
//                            WHERE t.deleted=0
//                              AND h.entity_id IS NULL
//                            ORDER BY t.sort_order ASC, t.$sortBy $sortOrder, t.id ASC) x
//                      WHERE x.id=$id";
//        } else {
//            $parentId = $this->getPDO()->quote($parentId);
//            $query = "SELECT x.position
//                      FROM (SELECT t.id, @rownum:=@rownum + 1 AS position
//                            FROM `$this->hierarchyTableName` h
//                                JOIN (SELECT @rownum:=0) r
//                                LEFT JOIN `$this->tableName` t ON t.id=h.entity_id
//                                LEFT JOIN `$this->tableName` t1 ON t1.id=h.parent_id
//                            WHERE h.parent_id=$parentId
//                              AND h.deleted=0
//                              AND t.deleted=0
//                              AND t1.deleted=0
//                            ORDER BY h.hierarchy_sort_order ASC, t.$sortBy $sortOrder, t.id ASC) x
//                      WHERE x.id=$id";
//        }
//
//        $position = $this->getPDO()->query($query)->fetch(\PDO::FETCH_COLUMN);
//
//        return (int)$position;
    }

    public function getInheritableFields(): array
    {
        $unInheritableFields = $this->getUnInheritableFields();

        $fields = [];

        foreach ($this->getMetadata()->get(['entityDefs', $this->entityType, 'fields'], []) as $field => $fieldData) {
            if (in_array($field, $fields) || in_array($field, $unInheritableFields)) {
                continue 1;
            }

            if (!empty($fieldData['notStorable'])) {
                continue 1;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    public function getUnInheritableFields(): array
    {
        $fields = array_merge($this->getMetadata()->get('app.nonInheritedFields', []), $this->getMetadata()->get(['scopes', $this->entityType, 'mandatoryUnInheritedFields'], []));
        $fields = array_merge($fields, $this->getMetadata()->get(['scopes', $this->entityType, 'unInheritedFields'], []));

        // add relations
        $fields = array_merge($fields, $this->getUnInheritedRelations());

        return $fields;
    }

    public function getUnInheritableRelations(): array
    {
        $result = array_merge([], $this->getMetadata()->get('app.nonInheritedRelations', []));
        $result = array_merge($result, $this->getMetadata()->get(['scopes', $this->entityType, 'mandatoryUnInheritedRelations'], []));
        $result = array_merge($result, $this->getMetadata()->get(['scopes', $this->entityType, 'unInheritedRelations'], []));

        foreach ($this->getMetadata()->get(['entityDefs', $this->entityType, 'links'], []) as $link => $linkDefs) {
            if (!empty($linkDefs['type']) && $linkDefs['type'] === 'hasMany') {
                if (empty($linkDefs['relationName'])) {
                    $result[] = $link;
                }
            }
        }

        return $result;
    }

    /**
     * @deprecated use getUnInheritableFields instead
     */
    public function getUnInheritedFields(): array
    {
        return $this->getUnInheritableFields();
    }

    /**
     * @deprecated use getUnInheritableRelations instead
     */
    public function getUnInheritedRelations(): array
    {
        return $this->getUnInheritableRelations();
    }

    public function fetchById(string $id): array
    {
        $result = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('id = :id')
            ->setParameter('id', $id)
            ->andWhere('deleted = :false')
            ->setParameter('false', false, Mapper::getParameterType(false))
            ->fetchAssociative();

        if (empty($result)) {
            return [];
        }

        $this->pushLinkMultipleFields($result);

        return $result;
    }

    public function pushLinkMultipleFields(array &$result): void
    {
        foreach ($this->getMetadata()->get(['entityDefs', $this->entityType, 'fields']) as $field => $fieldData) {
            if (
                array_key_exists('type', $fieldData)
                && $fieldData['type'] === 'linkMultiple'
                && array_key_exists('noLoad', $fieldData)
                && $fieldData['noLoad'] === false
                && !in_array($field, $this->getUnInheritedFields())
            ) {
                if (empty($entity)) {
                    $entity = $this->get($result['id']);
                }
                $result["{$field}_ids"] = array_column($entity->get($field)->toArray(), 'id');
                sort($result["{$field}_ids"]);
            }
        }
    }

    public function updatePositionInTree(string $entityId, string $position, string $target, string $parentId): void
    {
        $this->getConnection()->createQueryBuilder()
            ->delete($this->hierarchyTableName)
            ->andWhere('entity_id = :entityId')
            ->setParameter('entityId', $entityId)
            ->executeQuery();

        if (!empty($parentId)) {
            $this->getConnection()->createQueryBuilder()
                ->insert($this->hierarchyTableName)
                ->setValue('entity_id', ':entityId')
                ->setParameter('entityId', $entityId)
                ->setValue('parent_id', ':parentId')
                ->setParameter('parentId', $parentId)
                ->executeQuery();
        }

        $ids = array_column($this->getChildrenArray($parentId, false), 'id');
        unset($ids[array_search($entityId, $ids)]);
        $ids = array_values($ids);

        $sortedIds = [];
        if ($position === 'after') {
            foreach ($ids as $id) {
                $sortedIds[] = $id;
                if ($id === $target) {
                    $sortedIds[] = $entityId;
                }
            }
        } elseif ($position === 'inside') {
            $sortedIds = array_merge([$entityId], $ids);
        }

        foreach ($sortedIds as $k => $id) {
            $sortOrder = $k * 10;
            if (empty($parentId)) {
                $this->getConnection()->createQueryBuilder()
                    ->update($this->tableName)
                    ->set('sort_order', ':sortOrder')
                    ->setParameter('sortOrder', $sortOrder)
                    ->andWhere('id = :id')
                    ->setParameter('id', $id)
                    ->andWhere('deleted = :false')
                    ->setParameter('false', false, Mapper::getParameterType(false))
                    ->executeQuery();
            } else {
                $this->getConnection()->createQueryBuilder()
                    ->update($this->hierarchyTableName)
                    ->set('hierarchy_sort_order', ':sortOrder')
                    ->setParameter('sortOrder', $sortOrder)
                    ->andWhere('entity_id = :entityId')
                    ->setParameter('entityId', $id)
                    ->andWhere('deleted = :false')
                    ->setParameter('false', false, Mapper::getParameterType(false))
                    ->executeQuery();
            }
        }
    }

    public function hasMultipleParents(): bool
    {
        echo '<pre>';
        print_r('hasMultipleParents');
        die();

//        $query = "SELECT COUNT(e.id) as total
//                  FROM (SELECT entity_id FROM `$this->hierarchyTableName` WHERE deleted=0 GROUP BY entity_id HAVING COUNT(entity_id) > 1) AS rel
//                  LEFT JOIN `$this->tableName` e ON e.id=rel.entity_id
//                  WHERE e.deleted=0";
//
//        $count = $this
//            ->getPDO()
//            ->query($query)
//            ->fetch(\PDO::FETCH_COLUMN);
//
//        return !empty($count);
    }

    public function updateHierarchySortOrder(string $parentId, array $ids): void
    {
        foreach ($ids as $k => $id) {
            $sortOrder = $k * 10;
            $this->getConnection()->createQueryBuilder()
                ->update($this->hierarchyTableName)
                ->set('hierarchy_sort_order', ':sortOrder')
                ->setParameter('sortOrder', $sortOrder)
                ->where('parent_id = :parentId')
                ->setParameter('parentId', $parentId)
                ->andWhere('entity_id = :entityId')
                ->setParameter('entityId', $id)
                ->andWhere('deleted = :false')
                ->setParameter('false', false, Mapper::getParameterType(false))
                ->executeQuery();
        }
    }

    public function getParentsRecursivelyArray(string $id): array
    {
        $ids = [];
        $this->collectParents($id, $ids);

        return $ids;
    }

    public function getChildrenRecursivelyArray(string $id): array
    {
        $ids = [];
        $this->collectChildren($id, $ids);

        return $ids;
    }

    public function getChildrenArray(string $parentId, bool $withChildrenCount = true, int $offset = null, $maxSize = null, $selectParams = null): array
    {
        echo '<pre>';
        print_r('getChildrenArray');
        die();

//        $childWhere = "";
//        if ($selectParams) {
//            $childWhere = $this->getMapper()->getWhereQuery($this->entityType, $selectParams['whereClause']);
//            if (!empty($childWhere)) {
//                $childWhere = "AND " . str_replace($this->tableName . '.', 'e1.', $childWhere);
//            }
//        }
//
//        $select = 'e.*';
//        if ($withChildrenCount) {
//            $select .= ", (SELECT COUNT(r1.id) FROM `$this->hierarchyTableName` r1 JOIN `$this->tableName` e1 ON e1.id=r1.entity_id WHERE r1.parent_id=e.id AND e1.deleted=0 {$childWhere}) as childrenCount";
//        }
//
//        $where = "";
//        if ($selectParams) {
//            $where = $this->getMapper()->getWhereQuery($this->entityType, $selectParams['whereClause']);
//            if (!empty($where)) {
//                $where = "AND " . str_replace($this->tableName . '.', 'e.', $where);
//            }
//        }
//
//        $sortBy = Util::toUnderScore($this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'sortBy'], 'name'));
//        $sortOrder = !empty($this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'asc'])) ? 'ASC' : 'DESC';
//
//        if (empty($parentId)) {
//            $query = "SELECT {$select}
//                      FROM `$this->tableName` e
//                      WHERE e.id NOT IN (SELECT entity_id FROM `$this->hierarchyTableName` WHERE deleted=0)
//                      AND e.deleted=0
//                      {$where}
//                      ORDER BY e.sort_order ASC, e.$sortBy {$sortOrder}, e.id";
//        } else {
//            $parentId = $this->getPDO()->quote($parentId);
//            $query = "SELECT {$select}
//                  FROM `$this->hierarchyTableName` h
//                  LEFT JOIN `$this->tableName` e ON e.id=h.entity_id
//                  WHERE h.deleted=0
//                    AND e.deleted=0
//                    {$where}
//                    AND h.parent_id={$parentId}
//                  ORDER BY h.hierarchy_sort_order ASC, e.$sortBy {$sortOrder}, e.id";
//        }
//
//        if (!is_null($offset) && !is_null($maxSize)) {
//            $query .= " LIMIT $maxSize OFFSET $offset";
//        }
//
//        return $this->getPDO()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getChildrenCount(string $parentId, array $selectParams = null): int
    {
        echo '<pre>';
        print_r('getChildrenCount');
        die();

//        $where = "";
//        if ($selectParams) {
//            $where = $this->getMapper()->getWhereQuery($this->entityType, $selectParams['whereClause']);
//            if (!empty($where)) {
//                $where = "AND " . str_replace($this->tableName . '.', 'e.', $where);
//            }
//        }
//
//        if (empty($parentId)) {
//            $query = "SELECT COUNT(e.id) as count
//                      FROM `$this->tableName` e
//                      WHERE e.id NOT IN (SELECT entity_id FROM `$this->hierarchyTableName` WHERE deleted=0)
//                      AND e.deleted=0
//                      {$where}";
//        } else {
//            $query = "SELECT COUNT(e.id) as count
//                      FROM $this->tableName e
//                      LEFT JOIN $this->hierarchyTableName h on e.id=h.entity_id
//                      WHERE e.deleted=0
//                        AND h.deleted=0
//                        {$where}
//                        AND h.parent_id='$parentId'";
//        }
//
//        return (int)$this->getPDO()->query($query)->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    public function isRoot(string $id): bool
    {
        $record = $this->getConnection()->createQueryBuilder()
            ->select('h.id')
            ->from($this->hierarchyTableName, 'h')
            ->where('h.deleted = :false')
            ->setParameter('false', false, Mapper::getParameterType(false))
            ->andWhere('h.entity_id = :entityId')
            ->setParameter('entityId', $id)
            ->fetchAssociative();

        return empty($record);
    }


    public function getHierarchyRoute(string $id): array
    {
        $route = [];
        while (!empty($record = $this->getParentRecord($id))) {
            $route[$record['id']] = $record['name'];
            $id = $record['id'];
        }

        return array_reverse($route);
    }

    public function getParentRecord(string $id): array
    {
        $record = $this->getConnection()->createQueryBuilder()
            ->select('t.*')
            ->from($this->hierarchyTableName, 'h')
            ->leftJoin('h', $this->tableName, 't', 't.id = h.parent_id')
            ->where('h.deleted = :false')
            ->andWhere('t.deleted = :false')
            ->setParameter('false', false, Mapper::getParameterType(false))
            ->andWhere('h.entity_id = :entityId')
            ->setParameter('entityId', $id)
            ->fetchAssociative();

        return empty($record) ? [] : $record;
    }

    protected function entityHasArchive(Entity $entity): bool
    {
        return !empty($this->getMetadata()->get(['scopes', $entity->getEntityType(), 'hasArchive']));
    }


    protected function validateIsArchived(Entity $entity): void
    {
        $fieldName = 'isArchived';
        if ($entity->isAttributeChanged($fieldName) && $entity->get($fieldName) == true) {
            // search all childs
            $hasNonArchivedChildren = false;
            foreach ($entity->get('children') as $child) {
                if ($child->get('isArchived') == false) {
                    $hasNonArchivedChildren = true;
                    break;
                }
            }

            if ($hasNonArchivedChildren) {
                $language = $this->getLanguage();
                throw new BadRequest(
                    sprintf($language->translate('childsMustBeArchived', 'exceptions', 'Global'), $language->translate($fieldName, 'fields', $entity->getEntityType()))
                );
            }
        }
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        if (!empty($entity->get('parentsIds'))) {
            foreach ($entity->get('parentsIds') as $parentId) {
                $ids = array_merge($this->getParentsRecursivelyArray($parentId), [$parentId]);
                if (in_array($entity->get('id'), $ids)) {
                    throw new BadRequest("Child record cannot be chosen as a parent.");
                }
            }
        }

        if (!empty($entity->get('childrenIds'))) {
            foreach ($entity->get('childrenIds') as $childId) {
                $ids = array_merge($this->getChildrenRecursivelyArray($childId), [$childId]);
                if (in_array($entity->get('id'), $ids)) {
                    throw new BadRequest("Parent record cannot be chosen as a child.");
                }
            }
        }

        if ($this->entityHasArchive($entity)) {
            $this->validateIsArchived($entity);
        }

        $this->prepareSortOrder($entity);

        parent::beforeSave($entity, $options);
    }

    protected function prepareSortOrder(Entity $entity): void
    {
        if ($this->getMetadata()->get(['scopes', $entity->getEntityType(), 'type']) !== 'Hierarchy') {
            return;
        }

        if ($entity->get('sortOrder') === null) {
            $last = $this->where(['sortOrder!=' => null])->order('sortOrder', 'DESC')->findOne();
            $sortOrder = empty($last) ? 0 : $last->get('sortOrder') + 10;
            $entity->set('sortOrder', $sortOrder);
        }
    }

    protected function beforeRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        parent::beforeRelate($entity, $relationName, $foreign, $data, $options);

        if ($relationName === 'parents') {
            if (is_bool($foreign)) {
                throw new BadRequest("Action blocked. Please, specify {$this->entityType}.");
            }
            $foreign = is_string($foreign) ? $this->get($foreign) : $foreign;
            if (in_array($foreign->get('id'), $this->getChildrenRecursivelyArray($entity->get('id')))) {
                throw new BadRequest("Child record cannot be chosen as a parent.");
            }

            if (empty($this->getMetadata()->get(['scopes', $this->entityType, 'multiParents']))) {
                $parents = $entity->get('parents');
                if (!empty($parents) && count($parents) > 0) {
                    foreach ($parents as $parent) {
                        $this->unrelate($entity, 'parents', $parent);
                    }
                }
            }
        }

        if ($relationName === 'children') {
            if (is_bool($foreign)) {
                throw new BadRequest("Action blocked. Please, specify {$this->entityType}.");
            }
            $foreign = is_string($foreign) ? $this->get($foreign) : $foreign;
            if (in_array($foreign->get('id'), $this->getParentsRecursivelyArray($entity->get('id')))) {
                throw new BadRequest("Parent record cannot be chosen as a child.");
            }

            if (empty($this->getMetadata()->get(['scopes', $this->entityType, 'multiParents']))) {
                $parents = $foreign->get('parents');
                if (!empty($parents) && count($parents) > 0) {
                    foreach ($parents as $parent) {
                        $this->unrelate($foreign, 'parents', $parent);
                    }
                }
            }
        }
    }

    protected function createRoute(array $records, string $id, array &$route): void
    {
        foreach ($records as $record) {
            if ($record['entity_id'] === $id) {
                $route[] = $record['parent_id'];
                $this->createRoute($records, $record['parent_id'], $route);
            }
        }
    }

    protected function collectParents(string $id, array &$ids): void
    {
        $res = $this->getConnection()->createQueryBuilder()
            ->select('r.parent_id')
            ->from($this->hierarchyTableName, 'r')
            ->leftJoin('r', $this->tableName, 'm', 'r.parent_id = m.id')
            ->where('r.deleted = :false')
            ->andWhere('m.deleted = :false')
            ->setParameter('false', false, Mapper::getParameterType(false))
            ->andWhere('r.entity_id = :entityId')
            ->setParameter('entityId', $id)
            ->fetchAllAssociative();

        if (!empty($res)) {
            $res = array_column($res, 'parent_id');
            $ids = array_values(array_unique(array_merge($ids, $res)));
            foreach ($res as $v) {
                $this->collectParents($v, $ids);
            }
        }
    }

    protected function collectChildren(string $id, array &$ids): void
    {
        $res = $this->getConnection()->createQueryBuilder()
            ->select('r.entity_id')
            ->from($this->hierarchyTableName, 'r')
            ->leftJoin('r', $this->tableName, 'm', 'r.entity_id = m.id')
            ->where('r.deleted = :false')
            ->andWhere('m.deleted = :false')
            ->setParameter('false', false, Mapper::getParameterType(false))
            ->andWhere('r.parent_id = :parentId')
            ->setParameter('parentId', $id)
            ->fetchAllAssociative();

        if (!empty($res)) {
            $res = array_column($res, 'entity_id');
            $ids = array_values(array_unique(array_merge($ids, $res)));
            foreach ($res as $v) {
                $this->collectChildren($v, $ids);
            }
        }
    }
}