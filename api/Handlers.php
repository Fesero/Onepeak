<?
namespace B24;

use Bitrix\Main\Type;

class Events
{
    /**
     * Раскидывает по событиям
     * @param string $event
     * @param int $taskID
     * @param string $memberID
     * @return void
     */
    public static function provider(string $event, int $taskID, string $memberID):void
    {
        self::$event($taskID, $memberID);
    }

    /**
     * Событие обновления задачи в б24
     * @param int $taskID
     * @param string $memberID
     * @return void
     */
    private static function ONTASKUPDATE(int $taskID, string $memberID):void
    {
        \CRestExt::setCurrentBitrix24($memberID);

        $history = \CRestExt::call('tasks.task.history.list', [
            'taskId' => $taskID,
            'order' => [
                'ID' => 'DESC'
            ],
            'filter' => [
                '>=created_date' => date("d.m.Y"),
            ],
        ]);

        \Logger::writeLog($history, "app.events.history");

        $task = \CRestExt::call('tasks.task.get', ['taskId' => $taskID]);

        $task = $task['result']['task'];

        $ticket = \Api\Tasks::getTicketByB24($taskID, $memberID);

        \Logger::writeLog($ticket, "app.events.ticket");

        $updateID = 0;

        $params = [];

        do {
            $updateField = $history['result']['list'][$updateID]['field'];
            $updateValue = $history['result']['list'][$updateID]['value']['to'];

            if ($updateField === 'TITLE')
            {
                $params['title'] = $updateValue;
            }
            elseif ($updateField === 'STAGE')
            {
                $params['status'] = $updateValue;
            }
            elseif ($updateField === 'STATUS')
            {
                $params['closed'] = $updateValue == 5 ? 'Y' : 'N';
            }
            elseif ($updateField === 'RESPONSIBLE_ID')
            {
                $userB24 = \CRestExt::call('user.get', [
                    'ID' => $updateValue,
                ])['result'][0];

                $params['responsible'] = "{$userB24['NAME']} {$userB24['LAST_NAME']}";
            }

            $updateID++;
        } while ($history['result']['list'][$updateID]['createdDate'] === $history['result']['list'][$updateID - 1]['createdDate']);

        $params['updated_time'] = new Type\DateTime;
        \Api\Tasks::update($params, $ticket['ID']);
    }

    /**
     * Событие добавления задачи из б24
     * @param int $taskID
     * @param string $memberID
     * @return void
     */
    private static function ONTASKADD(int $taskID, string $memberID):void
    {
        \CRestExt::setCurrentBitrix24($memberID);

        $task = \CRestExt::call('tasks.task.get', ['taskId' => $taskID]);

        $task = $task['result']['task'];

        $data = [
            'member_id' => $memberID,
            'values' => [
                'TITLE' => $task['title'],
                'b24_id' => $taskID,
            ],
        ];

        $result = \Api\Tasks::save($data);

        \Logger::writeLog($result, "taskadd");
    }
}