<?php
namespace Api;

use Bitrix\Main\Type;
use Exception;

abstract class General 
{
    /**
     * @return string
     */
    public static function getOrmTable():string
    {
        return str_replace('Api', 'Orm', static::class) . 'Table';
    }

    /**
     * @return array
     */
    public static function get():array
    {
        return self::getOrmTable()::getList([
            'select' => ['*'],
        ]);
    }

    public static function drop():void
    {
        \Bitrix\Main\Application::getConnection()->dropTable(self::getOrmTable()::getTableName());
    }

    public static function createTable():void
    {
        self::getOrmTable()::getEntity()->createDbTable();
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public static function create(array $data):array
    {
        $result = self::getOrmTable()::add($data);

        if ($result->isSuccess()) {
            return ['status' => 'ok', 'text' => $result, 'id' => $result->getId()];
        } else {
            throw new Exception(print_r($result->getErrorMessages(), true));
        }
    }

    /**
     * @param string $searchID
     * @return array
     */
    public static function read(string $searchID):array
    {
        return self::getOrmTable()::getList([
            'select' => ['*'],
            'filter' => [
                'portal_id' => $searchID
            ],
        ])->fetchAll();
    }

    /**
     * @param array $data
     * @param string $ID
     * @return array
     * @throws Exception
     */
    public static function update(array $data, string $ID):array
    {
        $result = self::getOrmTable()::update($ID, $data);

        if ($result->isSuccess()) {
            return ['status' => 'ok', 'text' => $result, 'id' => $ID];
        } else {
            throw new Exception(print_r($result->getErrorMessages(), true));
        }
    }

    public static function truncate():void
    {
        \Bitrix\Main\Application::getConnection()->truncateTable(self::getOrmTable()::getTableName());
    }
}