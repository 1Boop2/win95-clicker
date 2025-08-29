<?php
// v3.3 Balanced shop definitions (ascending by base cost).
// Drop this file into /inc/shop_defs.php and include it from inc/lib.php (see README).

return [
  // CLICK ADDERS
  'mousepad95' => [
    'code'=>'mousepad95','name'=>'MousePad 95','desc'=>'Пыльный коврик. +0.1 клика за нажатие за уровень.',
    'icon'=>'🖱️','kind'=>'click_add','add'=>0.1,'base_cost'=>10,'growth'=>1.15,'max'=>50
  ],
  'mouse95' => [
    'code'=>'mouse95','name'=>'Мышь 95','desc'=>'Шариковая мышь. +1 клик за нажатие за уровень. (НЕРФ)',
    'icon'=>'🖱️','kind'=>'click_add','add'=>1.0,'base_cost'=>50,'growth'=>1.18,'max'=>30
  ],
  'doubleclick95' => [
    'code'=>'doubleclick95','name'=>'Double-Click 95','desc'=>'+5% к клику за уровень (множитель).',
    'icon'=>'🖱️','kind'=>'click_mult','mult'=>1.05,'base_cost'=>200,'growth'=>1.20,'max'=>40
  ],

  // AUTO CPS
  'autoclick_mk1' => [
    'code'=>'autoclick_mk1','name'=>'Автокликер Mk.I','desc'=>'+0.2 клика/сек за уровень.',
    'icon'=>'🤖','kind'=>'auto','cps'=>0.2,'base_cost'=>150,'growth'=>1.22,'max'=>100
  ],
  'ballmouse_pro' => [
    'code'=>'ballmouse_pro','name'=>'Ball Mouse Pro','desc'=>'+1 клик/сек за уровень.',
    'icon'=>'⚙️','kind'=>'auto','cps'=>1.0,'base_cost'=>700,'growth'=>1.22,'max'=>100
  ],
  'ps2_adapter' => [
    'code'=>'ps2_adapter','name'=>'PS/2 Адаптер','desc'=>'+3 клика/сек за уровень.',
    'icon'=>'🔌','kind'=>'auto','cps'=>3.0,'base_cost'=>1800,'growth'=>1.23,'max'=>100
  ],
  'wheel_upgrade' => [
    'code'=>'wheel_upgrade','name'=>'Колёсико прокрутки','desc'=>'+5 кликов/сек за уровень.',
    'icon'=>'🛞','kind'=>'auto','cps'=>5.0,'base_cost'=>3500,'growth'=>1.25,'max'=>100
  ],

  // MID/LATE GAME
  'optical_sensor' => [
    'code'=>'optical_sensor','name'=>'Оптический сенсор','desc'=>'+20% к клику за уровень (множитель).',
    'icon'=>'🔬','kind'=>'click_mult','mult'=>1.20,'base_cost'=>5000,'growth'=>1.25,'max'=>50
  ],
  'usb_2' => [
    'code'=>'usb_2','name'=>'USB 2.0','desc'=>'+10 кликов/сек за уровень.',
    'icon'=>'🧷','kind'=>'auto','cps'=>10.0,'base_cost'=>8000,'growth'=>1.28,'max'=>100
  ],
  'turbo_auto' => [
    'code'=>'turbo_auto','name'=>'Турбо-автокликер','desc'=>'+25 кликов/сек за уровень.',
    'icon'=>'🚀','kind'=>'auto','cps'=>25.0,'base_cost'=>25000,'growth'=>1.30,'max'=>100
  ],
  'usb_3' => [
    'code'=>'usb_3','name'=>'USB 3.0','desc'=>'+100 кликов/сек за уровень.',
    'icon'=>'💠','kind'=>'auto','cps'=>100.0,'base_cost'=>200000,'growth'=>1.30,'max'=>100
  ],
];
