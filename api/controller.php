<?
namespace SupportApi\Components;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use CBitrixComponent;
use Exception;

class SupportApiComponent extends CBitrixComponent implements Controllerable, Errorable
{
    protected ErrorCollection $errorCollection;

    /**
     * @param mixed $arParams
     * @return mixed
     */
    public function onPrepareComponentParams($arParams):mixed
    {
        $this->errorCollection = new ErrorCollection();
        return $arParams;
    }

    public function executeComponent()
    {
        // Метод не будет вызван при ajax запросе
    }

    public function getErrors(): array
    {
        return $this->errorCollection->toArray();
    }

    public function getErrorByCode($code): Error|null
    {
        return $this->errorCollection->getErrorByCode($code);
    }

    /**
     * Описываем действия
     * @return array[]
     */
    public function configureActions(): array
    {
        return [
            'save' => [
                'prefilters' => [

                ]
            ],
            'delete' => [
                'prefilters' => [

                ]
            ],
            'createTicket' => [
                'prefilters' => [

                    ]
            ],
            'addMessage' => [
                'prefilters' => [

                    ]
            ],
        ];
    }

    /**
     * Сохранение данных
     * @param string $method
     * @param array $values
     * @param string|null $memberID
     * @return string[]
     * @throws Exception
     */
    public function saveAction(string $method, array $values, ?string $memberID = null):array
    {
        $values = array_column($values, 1, 0);
        $data['values'] = $values;

        $memberID ??= \B24\General::getMemberID();

        if (empty($memberID))
        {
            throw new Exception('Не задан member_id');
        }

        $data['member_id'] = $memberID;

        if (!empty($values['caption']))
        {
            $data['caption'] = $values['caption'];
        }

        return ("\\Api\\" . ucfirst($method))::save($data);
    }

    /**
     * Удаление данных
     * @param string $method
     * @param array $values
     * @param string|null $memberID
     * @return array
     * @throws Exception
     */
    public function deleteAction(string $method, array $values, ?string $memberID = null):array
    {
        $memberID ??= \B24\General::getMemberID();

        if (empty($memberID))
        {
            throw new Exception('Не задан member_id');
        }

        return ("\\Api\\" . ucfirst($method))::delete($values['code'], $memberID);
    }

    /**
     * Создание задачи в б24
     * @param array $values
     * @param string|null $memberID
     * @return array
     * @throws Exception
     */
    public function createTicketAction(array $values, ?string $memberID = null):array
    {
        $memberID ??= \B24\General::getMemberID();

        if (empty($memberID))
        {
           throw new Exception(('Не задан member_id'));
        }

        $values = array_column($values, 1, 0);

        return \B24\General::createTicket($values, $memberID);
    }

    /**
     * Добавление комментария в тикет в саппорте и б24
     * @param array $values
     * @param string|null $memberID
     * @return array
     * @throws Exception
     */
    public function addMessageAction(array $values, ?string $memberID = null):array
    {
        $memberID ??= \B24\General::getMemberID();

        if (empty($memberID))
        {
            throw new Exception(('Не задан member_id'));
        }

        $values = array_column($values, 1, 0);

        ['result' => $result, 'time' => $time] = \B24\General::addMessage($values, $memberID);

        \Api\Messages::save([
            'member_id' => $memberID,
            'values' => [
                ...$values,
                ...[
                    'b24_id' => $result,
                    'author_id' => 1,
                    'author_name' => 'name',
                    'files' => '1, 2, 3, 4',
                ]
            ]
        ]);

        return ['result' => $result];
    }
}