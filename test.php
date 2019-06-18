<?php

use infrajs\mail\Mail;
use infrajs\session\Session;


print_r(Session::getLink());
exit;

Mail::html('Тест','<h1>Проверка</h1>',true,'axlbant@sent.com', 2);