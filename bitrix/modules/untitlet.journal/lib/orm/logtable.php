<?php
/**
 * ORM класс для таблицы логов согласий
 */

namespace Untitlet\Journal\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

class LogTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName()
    {
        return 'b_untitlet_journal_log';
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap()
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'readonly' => true
            ]),
            
            new IntegerField('USER_ID', [
                'nullable' => true
            ]),
            
            new StringField('SESSION_ID', [
                'size' => 64,
                'nullable' => true
            ]),
            
            new StringField('IP', [
                'size' => 45,
                'nullable' => true
            ]),
            
            new DatetimeField('TIMESTAMP', [
                'required' => true,
                'default_value' => new \Bitrix\Main\DB\SqlExpression('NOW()')
            ]),
            
            new IntegerField('CONSENT_VERSION_ID', [
                'required' => true
            ]),
            
            new StringField('FORM_ID', [
                'size' => 128,
                'nullable' => true
            ]),
            
            new StringField('PAGE_URL', [
                'size' => 512,
                'nullable' => true
            ]),
            
            new TextField('USER_AGENT', [
                'nullable' => true
            ]),
            
            new EnumField('CONSENT_TYPE', [
                'required' => true,
                'default_value' => 'form_checkbox',
                'values' => [
                    'banner',
                    'form_checkbox',
                    'profile_update',
                    'api'
                ]
            ]),
            
            new EnumField('STATUS', [
                'required' => true,
                'default_value' => 'granted',
                'values' => [
                    'granted',
                    'withdrawn'
                ]
            ]),
            
            new TextField('META', [
                'nullable' => true,
                'serialization' => [
                    'type' => 'json'
                ]
            ]),
            
            // Связь с таблицей версий текстов
            new Reference(
                'CONSENT_VERSION',
                TextVersionTable::class,
                Join::on('this.CONSENT_VERSION_ID', 'ref.ID')
            )
        ];
    }

    /**
     * Запрет на обновление записей (INSERT only)
     * 
     * @param mixed $primary
     * @param array $data
     * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function update($primary, $data)
    {
        // Логирование попытки изменения в event_log
        \Bitrix\Main\EventManager::getInstance()->sendEventImmediately(
            'main',
            'OnEventLogEntryAdd',
            [
                'MODULE_ID' => 'untitlet.journal',
                'EVENT_ID' => 'UNTITLET_JOURNAL_UPDATE_ATTEMPT',
                'MESSAGE' => 'Попытка обновления записи лога ID=' . (int)$primary,
                'DETAILS' => serialize($data)
            ]
        );
        
        // Возвращаем ошибку - запись нельзя изменить
        $result = new \Bitrix\Main\ORM\Data\UpdateResult();
        $result->addError(new \Bitrix\Main\ORM\Entity\Error(
            'Записи журнала согласий нельзя изменять. Только удаление через администратора.',
            'JOURNAL_IMMUTABLE'
        ));
        
        return $result;
    }

    /**
     * Кастомное удаление с проверкой прав
     */
    public static function delete($primary)
    {
        // Проверка прав доступа
        global $APPLICATION;
        if (!$APPLICATION->GetGroupRight('untitlet.journal') >= 'W') {
            $result = new \Bitrix\Main\ORM\Data\DeleteResult();
            $result->addError(new \Bitrix\Main\ORM\Entity\Error(
                'Недостаточно прав для удаления записей журнала',
                'NO_RIGHTS'
            ));
            return $result;
        }
        
        return parent::delete($primary);
    }
}
