<?php

/*
|--------------------------------------------------------------------------
| Ramadan defaults
|--------------------------------------------------------------------------
|
| Every new masjid starts with these sehri and iftar duas (in Telugu). After
| that the masjid admin adds, edits and removes duas on the admin Ramadan page;
| they are stored per masjid in the ramadan_duas table.
|
*/

return [
    'default_duas' => [
        [
            'type' => 'sehri',
            'title' => 'సహరీ దుఆ (రోజా నియ్యత్)',
            'body' => 'నవైతు అన్ అసూమ గదన్ లిల్లాహి తఆలా మిన్ సౌమి రమదాన్',
        ],
        [
            'type' => 'iftar',
            'title' => 'ఇఫ్తార్ దుఆ',
            'body' => 'అల్లాహుమ్మ లక సుమ్తు వ బిక ఆమన్తు వ అలైక తవక్కల్తు వ అలా రిజ్ఖిక అఫ్తర్తు ఫతఖబ్బల్ మిన్నీ',
        ],
    ],
];
