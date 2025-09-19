<?php

//1 https://regex101.com/r/bYeoyR/1
//$str = "RegExr was created by gskinner.com, and is proudly hosted by Media Temple.";
//preg_match_all('/[aeio]/', $str, $matches);
//print_r($matches);

//2 https://regex101.com/r/TxPlpc/1
//$str = "RegExr was created by gskinner.com, and is proudly hosted by Media Temple.";
//preg_match_all('/[^aeio]/', $str, $matches);
//print_r($matches);

//3 https://regex101.com/r/H7QqBS/1
//$str = "Explore results with the Tools below.";
//preg_match_all('/[g-s]/', $str, $matches);
//print_r($matches);

//4 https://regex101.com/r/5ppzP8/1
//$str = "glib jocks vex dwarves!";
//preg_match_all('/./', $str, $matches);
//print_r($matches);

//5 https://regex101.com/r/VLHjFB/1
//$str = "RegExr was created by gskinner.com, and is proudly hosted by Media Temple.";
//preg_match_all('/\w+/', $str, $matches);
//print_r($matches);

//6 https://regex101.com/r/eumUTB/1
//$str = "RegExr was created by gskinner.com, and is proudly hosted by Media Temple.";
//preg_match_all('/\W+/', $str, $matches);
//print_r($matches);

//7 https://regex101.com/r/RgNeYF/1
//$str = "+1-(444)-555-1234";
//preg_match_all('/\d/', $str, $matches);
//print_r($matches);

//8 https://regex101.com/r/jiR8e6/1
//$str = "RegExr was created by gskinner.com, and is proudly hosted by Media Temple.";
//preg_match_all('/\D/', $str, $matches);
//print_r($matches);

//9 https://regex101.com/r/ECTRUG/1
//$str = "glib jocks vex dwarves!";
//preg_match_all('/\s/', $str, $matches);
//print_r($matches);

//10 https://regex101.com/r/V8yH9r/1
//$str = "glib jocks vex dwarves!";
//preg_match_all('/\S/', $str, $matches);
//print_r($matches);

//11 https://regex101.com/r/1t5ve8/1
//   https://regex101.com/r/qZSoNW/1
//$str = "RegExr was created by gskinner.com, and is proudly hosted by Media Temple.\nRegExr sells seashells";
//preg_match_all('/^RegExr/m', $str, $matches);
//preg_match_all('/^(?:(?!\bRegExr\b).)*?\K\bRegExr\b/m', $str, $matches);
//print_r($matches);

//12 https://regex101.com/r/Zx0J6Z/1
//$str = "RegExr was created RegExr by gskinner.com, and is proudly hosted by Media Temple RegExr.\nRegExr sells Temple RegExr.";
//preg_match_all('/\bTemple\b(?!.*\bTemple\b)/m', $str, $matches);
//print_r($matches);

//13 https://regex101.com/r/6TJEZH/1
//$str = "hahaha haa hah!";
//preg_match_all('/(?:ha)+/', $str, $matches);
//print_r($matches);

//14 https://regex101.com/r/pc6b92/1
//$str = "hahaha haa hah!";
//preg_match_all('/(?<name_ha>(?:ha)+)/', $str, $matches);
//print_r($matches);

// 15 https://regex101.com/r/WtbEOq/1
//$str = "b be bee beer beers";
//preg_match_all('/\bb\w+\b/', $str, $matches);
//print_r($matches);

//16 https://regex101.com/r/W62haM/1
//$str = "b be bee beer beers";
//preg_match_all('/\bb\w*\b/', $str, $matches);
//print_r($matches);

//17 https://regex101.com/r/pZV1Mg/1
//$str = "b be bee beer beers";
//preg_match_all('/\bb\w{2,}\b/', $str, $matches);
//print_r($matches);

//18 https://regex101.com/r/kjybds/1
//$str = "color colour";
//preg_match_all('/\b\w*o\w*ou?\w*\b/', $str, $matches);
//print_r($matches);

//19 https://regex101.com/r/X2Eutf/1
//$str = "1pt 2px 3em 4px";
//preg_match_all('/.(?=px)/', $str, $matches);
//print_r($matches);

//20 https://regex101.com/r/Khrnxu/1
//$str = "1pt 2px 3em 4px";
//preg_match_all('/.(?!px)/', $str, $matches);
//print_r($matches);

//21 https://regex101.com/r/wYshhY/1
//   https://regex101.com/r/GeYCuc/1
//$str = "89281111111
//8-928-111-11-11
//8 928 111 11 11
//8(928) 111-11-11
//+7 928 111 11 11";
//preg_match_all('/^(?:\+7|8)[\s\-()]*\d{3}[\s\-()]*?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/m', $str, $matches);
//preg_match_all('/^(?:\+7|8)[\s\-()]?\d{3}[\s\-()]{0,2}\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/m', $str, $matches);
//preg_match_all('/^(?=(?:\D*\d){11}\D*$)\+?[\d\-()\h]*$/m', $str, $matches);
//print_r($matches);