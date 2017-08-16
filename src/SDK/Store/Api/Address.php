<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\SDK\Store\Api;

use Eelly\DTO\UserDTO;
use Eelly\SDK\Store\Service\AddressInterface;
use Eelly\SDK\Store\Service\DTO\AddressDTO;

/**
 * @author eellytools<localhost.shell@gmail.com>
 */
class Address implements AddressInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Eelly\SDK\Store\Service\AddressInterface::addStoreAddress()
     */
    public function addStoreAddress(array $addrData, UserDTO $user = null): bool
    {
        return EellyClient::request('address/addStoreAddress', __FUNCTION__, $addrData);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Eelly\SDK\Store\Service\AddressInterface::updateStoreAddress()
     */
    public function updateStoreAddress(array $addrData, UserDTO $user = null): bool
    {
        return EellyClient::request('address/updateStoreAddress', __FUNCTION__, $addrData);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Eelly\SDK\Store\Service\AddressInterface::deleteStoreAddress()
     */
    public function deleteStoreAddress(int $addrId, UserDTO $user = null): bool
    {
        return EellyClient::request('address/deleteStoreAddress', __FUNCTION__, $addrId);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Eelly\SDK\Store\Service\AddressInterface::getStoreAddress()
     */
    public function getStoreAddress(int $storeId, int $addressType, UserDTO $user = null): AddressDTO
    {
        return EellyClient::request('address/getStoreAddress', __FUNCTION__, $storeId, $addressType);
    }

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        static $instance;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }
}
