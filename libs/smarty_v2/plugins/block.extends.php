<?php

/**
 * Блок, наследующий шаблон
 * 
 * @param  array   $params   Список параметров, указанных в вызове блока
 * @param  string  $content  Текст между тегами {extends}..{/extends}
 * @param  Smarty  $smarty   Ссылка на объект Smarty
 */
function smarty_block_extends($params, $content, extSmarty $smarty)
{
    /** Никому не доверяйте. Даже себе! */
    if (false === array_key_exists('template', $params)) {
        $smarty->trigger_error('Укажите шаблон, от которого наследуетесь!');
    }

    return $smarty->fetch($params['template']);
}

?>
