<?php

namespace Evk\BxMigrate\Migrate\Traits;

use Evk\BxMigrate\Migrate\Exception;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Highloadblock\HighloadBlockRightsTable;
use Bitrix\Main\GroupTable;
use Bitrix\Main\TaskTable;

/**
 * Трэйт с функциями для высоконагруженных инфоблоков.
 */
trait HlBlock
{
    /**
     * @param array $data
     *
     * @return array
     *
     * @throws \Evk\BxMigrate\Migrate\Exception
     */
    protected function HLCreate(array $data)
    {
        $return = [];
        if (empty($data['NAME'])) {
            throw new Exception('You must set hl NAME');
        }
        if (empty($data['TABLE_NAME'])) {
            throw new Exception('You must set hl TABLE_NAME');
        }
        if ($id = $this->HLGetIdByCode($data['NAME'])) {
            throw new Exception("Hl entity with name {$data['NAME']} ({$id}) already exists");
        }
        $arLoad = $data;
        unset($arLoad['LANGS'], $arLoad['RIGHTS']);
        $result = HighloadBlockTable::add($arLoad);
        if ($result->isSuccess()) {
            if (!empty($data['LANGS'])) {
                $this->HLSetLangs($result->getId(), $data['LANGS']);
            }
            if (!empty($data['RIGHTS'])) {
                $this->HLSetRights($result->getId(), $data['RIGHTS']);
            }
            $return[] = "Add {$data['NAME']} (" . $result->getId() . ') highload block';
        } else {
            throw new Exception("Can't create {$data['NAME']} highload block: " . implode(', ', $result->getErrorMessages()));
        }

        return $return;
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws \Evk\BxMigrate\Migrate\Exception
     */
    protected function HLUpdate(array $data)
    {
        $return = [];
        if (empty($data['NAME'])) {
            throw new Exception('You must set NAME');
        }
        if ($id = $this->HLGetIdByCode($data['NAME'])) {
            $arLoad = $data;
            unset($arLoad['LANGS'], $arLoad['RIGHTS'], $arLoad['NAME']);
            if ($arLoad) {
                $result = HighloadBlockTable::update($id, $arLoad);
                if ($result->isSuccess()) {
                    if (!empty($data['LANGS'])) {
                        $this->HLSetLangs($id, $data['LANGS']);
                    }
                    if (!empty($data['RIGHTS'])) {
                        $this->HLSetRights($id, $data['RIGHTS']);
                    }
                    $return[] = "Update {$data['NAME']} ({$id}) highload block";
                } else {
                    throw new Exception("Can't update {$data['NAME']} ({$id}) highload block: " . implode(', ', $result->getErrorMessages()));
                }
            } elseif (!empty($data['RIGHTS'])) {
                $this->HLSetRights($id, $data['RIGHTS']);
            } elseif (!empty($data['LANGS'])) {
                $this->HLSetLangs($id, $data['LANGS']);
            }
        } else {
            throw new Exception("Hl entity with name {$data['NAME']} does not exist");
        }

        return $return;
    }

    /**
     * @param string $entity
     *
     * @return array
     *
     * @throws \Evk\BxMigrate\Migrate\Exception
     */
    protected function HLDelete($entity)
    {
        $return = [];
        $id = $this->HLGetIdByCode($entity);
        if ($id) {
            $res = HighloadBlockTable::delete($id);
            if ($res->isSuccess()) {
                $return[] = "Delete highload block {$entity} ({$id})";
            } else {
                throw new Exception("Can't delete {$entity} ({$id}) highload block: " . implode(', ', $res->getErrorMessages()));
            }
        } else {
            throw new Exception("Hl entity with name {$entity} does not exist");
        }

        return $return;
    }

    /**
     * @param string $entity
     *
     * @return mixed
     */
    protected function HLGetIdByCode($entity)
    {
        $filter = [
            'filter' => ['=NAME' => $entity],
        ];
        $hlblock = HighloadBlockTable::getRow($filter);

        return !empty($hlblock['ID']) ? $hlblock['ID'] : null;
    }

    /**
     * Задает параметры прав доступа для hl блока.
     *
     * @param int   $hlId   Идентификатор блока
     * @param array $rights Массив прав
     *
     * @throws \Evk\BxMigrate\Migrate\Exception
     */
    protected function HLSetRights($hlId, array $rights)
    {
        $res = HighloadBlockRightsTable::getList([
            'filter' => ['HL_ID' => $hlId],
        ]);
        while ($right = $res->fetch()) {
            HighloadBlockRightsTable::delete($right['ID']);
        }

        $groups = [];
        $res = GroupTable::getList();
        while ($group = $res->fetch()) {
            $groups[$group['STRING_ID']] = $group;
        }

        $tasks = [];
        $res = TaskTable::getList(['filter' => ['=MODULE_ID' => 'highloadblock']]);
        while ($task = $res->fetch()) {
            $tasks[$task['LETTER']] = $task;
        }

        foreach ($rights as $groupCode => $taskLetter) {
            if (!isset($groups[$groupCode])) {
                throw new Exception("Can't find group {$groupCode}");
            }
            if (!isset($tasks[$taskLetter])) {
                throw new Exception("Can't find task {$taskLetter}");
            }
            $res = HighloadBlockRightsTable::add([
                'HL_ID' => $hlId,
                'ACCESS_CODE' => "G{$groups[$groupCode]['ID']}",
                'TASK_ID' => $tasks[$taskLetter]['ID'],
            ]);
            if (!$res->isSuccess()) {
                throw new Exception("Can't creare {$groupCode} {$taskLetter} right for highload block: " . implode(', ', $res->getErrorMessages()));
            }
        }
    }

    /**
     * Задает языковые параметры для hl блока.
     *
     * @param int   $hlId  Идентификатор блока
     * @param array $langs Массив переводов
     *
     * @throws \Evk\BxMigrate\Migrate\Exception
     */
    protected function HLSetLangs($hlId, array $langs)
    {
        $hlId = (int) $hlId;
        if (!$hlId) {
            throw new Exception('Empty Hl id for langs');
        }

        $res = HighloadBlockLangTable::getList([
            'filter' => ['ID' => $hlId],
        ]);
        while ($loc = $res->fetch()) {
            HighloadBlockLangTable::delete($loc['ID']);
        }

        foreach ($langs as $langId => $langValue) {
            $langRes = HighloadBlockLangTable::add([
                'ID' => $hlId,
                'LID' => $langId,
                'NAME' => $langValue,
            ]);
            if (!$langRes->isSuccess()) {
                throw new Exception("Can't create lang {$langId} for {$hlId} highload block: " . implode(', ', $langRes->getErrorMessages()));
            }
        }
    }
}
