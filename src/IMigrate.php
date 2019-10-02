<?php

namespace Evk\BxMigrate;

/**
 * Интерфейс для экземпляра миграции. Миграция должна уметь применить себя в базу и откатить обратно.
 */
interface IMigrate
{
    /**
     * Применяет миграцию для базы данных. Возвращает массив строковых сообщений,
     * которые описывают, какие действия были осуществлены миграцией.
     *
     * @return array
     */
    public function managerUp();

    /**
     * Откатывает миграцию для базы данных. Возвращает массив строковых сообщений,
     * которые описывают, какие действия были осуществлены миграцией.
     *
     * @return array
     */
    public function managerDown();
}