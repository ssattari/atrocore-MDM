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

namespace Atro\ActionTypes;

use Atro\Core\Container;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ServiceFactory;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class Update implements TypeInterface
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function executeNow(Entity $action, \stdClass $input): bool
    {
        $actionData = $action->get('data');

        if (empty($action->get('selfTargeted'))) {
            echo '<pre>';
            print_r('not self targeted');
            die();
        }

        if (!property_exists($input, 'entityId')) {
            throw new BadRequest('entityId is required.');
        }

        $entity = $this->getEntityManager()->getRepository($action->get('entityType'))->get($input->entityId);
        if (empty($entity)) {
            return false;
        }

        $inputData = null;
        switch ($actionData->field->updateType) {
            case 'basic':
                $inputData = $actionData->fieldData ?? null;
                break;
            case 'script':
                if (!empty($actionData->field->updateScript)) {
                    $outputJson = $this->container->get('twig')->renderTemplate($actionData->field->updateScript, ['entity' => $entity]);
                    $input = @json_decode((string)$outputJson);
                    if ($input !== null) {
                        $inputData = $input;
                    }
                }
                break;
        }

        if ($inputData === null) {
            return false;

        }

        $this->getServiceFactory()->create($action->get('entityType'))->updateEntity($entity->get('id'), $inputData);

        return true;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    protected function getServiceFactory(): ServiceFactory
    {
        return $this->container->get('serviceFactory');
    }
}