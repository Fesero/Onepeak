<?php
namespace Api;
use Interfaces;

class Groups extends General implements Interfaces\Api
{
    /**
     * @param array{member_id: string, values: array} $data
     * @return array
     */
    public static function save(array $data):array
    {
        if (!\Bitrix\Main\Application::getConnection()->isTableExists(self::getOrmTable()::getTableName())) {
            self::createTable();
        }

        $elements = !empty($data['member_id'])
            ? self::getOrmTable()::query()->setSelect(['*'])->whereIn('portal_id', $data['member_id'])->exec()->fetchAll()
            : [];

        $findElem = [];

        foreach ($elements as $element) {
            $findElem[$element['name']] = $element['ID'];
        }

        $result = [];

        foreach ($data['values'] as &$value) {
            $params = [
                'portal_id' => $data['member_id'],
                'name' => $value['group'],
            ];

            try {
                if (empty($findElem[$value['group']])) {
                    $res = self::create($params);
                    $result[] = $res;
                    $findElem[$value['group']] = $res['id'];
                }
                $value['group_id'] = $findElem[$value['group']];

            } catch (\Exception $exception) {
                return ['status' => 'error', 'text' => $exception->getMessage()];
            }
        }

        $result[] = GroupsUser::save($data);

        return $result;
    }

    /**
     * @param string $searchID
     * @param string $memberID
     * @return array
     */
    public static function delete(string $searchID, string $memberID):array
    {
        $elem = self::getOrmTable()::query()->setSelect(['*'])->whereIn('portal_id', $memberID)->where('name', $searchID)->exec()->fetch();
        if (!empty($elem))
        {
            $result = self::getOrmTable()::delete($elem['ID']);
            if ($result->isSuccess()) {
                $result = ['status' => 'ok'];
            } else {
                $errorText = '';
                foreach ($result->getErrorMessages() as $error) {
                    $errorText .= $error . "\n";
                }
                $result = ['status' => 'error', 'text' => $errorText];
            }
        } else {
            $result = ['status' => 'error', 'text' => 'Группа не найдена'];
        }

        return $result;
    }
}