<?php

/*
|--------------------------------------------------------------------------
| Ramadan duas
|--------------------------------------------------------------------------
|
| Shown with the Ramadan calendar on the admin dashboard, the public site and
| the API (and so the mobile app). Each dua has the Arabic text, a Latin and a
| Telugu transliteration, and its meaning in Telugu and English. Have the imam
| review any change to the wording.
|
*/

return [
    'duas' => [
        [
            'key' => 'sehri',
            'title' => 'Sehri: intention for the fast',
            'title_te' => 'సహరీ దుఆ (రోజా నియ్యత్)',
            'arabic' => 'نَوَيْتُ أَنْ أَصُومَ غَدًا لِلّٰهِ تَعَالٰى مِنْ صَوْمِ رَمَضَانَ',
            'transliteration' => "Nawaitu an asuma ghadan lillahi ta'ala min sawmi Ramadan",
            'telugu' => 'నవైతు అన్ అసూమ గదన్ లిల్లాహి తఆలా మిన్ సౌమి రమదాన్',
            'meaning_te' => 'నేను అల్లాహ్ తఆలా కొరకు రేపటి రమజాన్ రోజా (ఉపవాసం) ఉండాలని నియ్యత్ చేసుకుంటున్నాను.',
            'meaning_en' => "I intend to keep tomorrow's Ramadan fast for the sake of Allah Most High.",
            'note_te' => 'నియ్యత్ మనసులో చేసుకుంటే సరిపోతుంది; ఈ పదాలు నోటితో పలకడం తప్పనిసరి కాదు.',
            'source' => null,
        ],
        [
            'key' => 'iftar',
            'title' => 'Iftar: when breaking the fast',
            'title_te' => 'ఇఫ్తార్ దుఆ',
            'arabic' => 'اَللّٰهُمَّ لَكَ صُمْتُ وَبِكَ آمَنْتُ وَعَلَيْكَ تَوَكَّلْتُ وَعَلٰى رِزْقِكَ أَفْطَرْتُ فَتَقَبَّلْ مِنِّي',
            'transliteration' => "Allahumma laka sumtu wa bika aamantu wa 'alaika tawakkaltu wa 'ala rizqika aftartu fataqabbal minni",
            'telugu' => 'అల్లాహుమ్మ లక సుమ్తు వ బిక ఆమన్తు వ అలైక తవక్కల్తు వ అలా రిజ్ఖిక అఫ్తర్తు ఫతఖబ్బల్ మిన్నీ',
            'meaning_te' => 'ఓ అల్లాహ్! నీ కోసమే నేను రోజా ఉన్నాను, నిన్నే విశ్వసించాను, నీపైనే భరోసా ఉంచాను, నీవు ప్రసాదించిన ఆహారంతోనే రోజా విరమిస్తున్నాను; నా నుండి స్వీకరించు.',
            'meaning_en' => 'O Allah, for You I fasted, in You I believe, in You I put my trust and with Your provision I break my fast; accept it from me.',
            'note_te' => null,
            'source' => null,
        ],
    ],
];
