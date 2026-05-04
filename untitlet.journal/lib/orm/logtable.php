<?php
namespace Untitlet\Journal\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\JsonField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

class LogTable extends DataManager
{
    public static function getTableName()
    {
        return 'b_untitlet_journal_log';
    }

    public static function getMap()
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            
            new IntegerField('USER_ID', [
                'nullable' => true,
                'default_value' => 0,
            ]),
            
            new StringField('SESSION_ID', [
                'size' => 64,
                'required' => true,
            ]),
            
            new StringField('IP', [
                'size' => 45,
                'required' => true,
            ]),
            
            new DatetimeField('TIMESTAMP', [
                'required' => true,
                'default_value' => new \Bitrix\Main\DB\SqlExpression('NOW()'),
            ]),
            
            new IntegerField('CONSENT_VERSION_ID', [
                'required' => true,
            ]),
            
            new StringField('FORM_ID', [
                'size' => 128,
                'required' => true,
            ]),
            
            new StringField('PAGE_URL', [
                'size' => 512,
                'required' => true,
            ]),
            
            new StringField('USER_AGENT', [
                'required' => true,
            ]),
            
            new EnumField('CONSENT_TYPE', [
                'values' => [
                    'banner' => 'Banner',
                    'form_checkbox' => 'Form Checkbox',
                    'profile_update' => 'Profile Update',
                    'api' => 'API',
                ],
                'required' => true,
            ]),
            
            new EnumField('STATUS', [
                'values' => [
                    'granted' => 'Granted',
                    'withdrawn' => 'Withdrawn',
                ],
                'required' => true,
                'default_value' => 'granted',
            ]),
            
            new JsonField('META', [
                'nullable' => true,
            ]),
            
            // Relations
            new Reference(
                'CONSENT_VERSION',
                TextVersionTable::class,
                Join::on('this.CONSENT_VERSION_ID', 'ref.ID')
            ),
        ];
    }
}
