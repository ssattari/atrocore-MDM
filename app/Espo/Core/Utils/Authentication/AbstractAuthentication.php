<?php
/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Espo\Core\Utils\Authentication;

use Espo\Core\Container;
use Espo\Core\Utils\Auth;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\PasswordHash;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

/**
 * Class AbstractAuthentication
 */
abstract class AbstractAuthentication
{
    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    private $passwordHash;

    /**
     * AbstractAuthentication constructor.
     *
     * @param Auth      $auth
     * @param Container $container
     */
    public function __construct(Auth $auth, Container $container)
    {
        $this->auth = $auth;
        $this->container = $container;
    }

    abstract public function login(string $username, string $password, array $context = null): ?User;

    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    protected function getPasswordHash(): PasswordHash
    {
        if (!isset($this->passwordHash)) {
            $this->passwordHash = new PasswordHash($this->getConfig());
        }

        return $this->passwordHash;
    }
}
