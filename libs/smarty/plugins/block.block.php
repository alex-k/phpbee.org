<?php

/**
 * Создаёт именованные блоки в тексте шаблона
 * 
 * @param  array   $params   Список параметров, указанных в вызове блока
 * @param  string  $content  Текст между тегами {extends}..{/extends}
 * @param  Smarty  $smarty   Ссылка на объект Smarty
 */
function smarty_block_block($params, $content, extSmarty $smarty)
{
    if (array_key_exists('name', $params) === false) {
        $smarty->trigger_error('Не указано имя блока');
    }

    $name = $params['name'];

    if ($content) {
        $smarty->setBlock($name, $content);
    }

    return $smarty->getBlock($name);
}
