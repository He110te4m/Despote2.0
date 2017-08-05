<?php
/**
 *
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace App\Controller;

class Index extends \Despote\Base\Controller
{
    public function index()
    {
        $this->render('index.php', [], 'Default.php');
    }
}
