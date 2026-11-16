<?php
require __DIR__.'/../vendor/autoload.php';
use UsosProxy\Calendar;
function check($condition) { if (!$condition) throw new RuntimeException('Test failed'); }
check(Calendar::title('Tworzenie aplikacji dla systemu iOS/iPadOS',[])==='TAdSi');
check(Calendar::title('Ethics in research and scientific work (en)',[])==='EiRaSW');
check(Calendar::title('Workshop in writing and preparation of scientific publications - option 3',[])==='WiWaPoSP');
check(Calendar::title('Workshop in writing and preparation of scientific publications – option 3',[])==='WiWaPoSP');
check(Calendar::title('Systemy klient-serwer',[])==='SK');
check(Calendar::title('Ethics in research and scientific work (en)',['Ethics in research and scientific work (en)'=>'Ethics'])==='Ethics');
$ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//PL\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTART:20261116T100000Z\r\nSUMMARY:CWL - Test i programowanie\r\nDESCRIPTION:Sala: 315\\nC-2\\n\\nhttps://example.org/?gr_nr=4\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
$result = Calendar::merge([['id'=>'123','initials'=>'AB','ics'=>$ics]],[],false);
$cal = Sabre\VObject\Reader::read($result);
check((string)$cal->VEVENT->SUMMARY==='[AB] TiP lab (gr. 4)');
check((string)$cal->VEVENT->LOCATION==='C-2/315');
check(!str_contains((string)$cal->VEVENT->DESCRIPTION,'Sala:'));
check((string)$cal->VEVENT->DESCRIPTION==="Przedmiot: Test i programowanie\n\nhttps://example.org/?gr_nr=4");
$minimal = new Sabre\VObject\Component\VCalendar();
$minimal->add('VEVENT', ['UID'=>'minimal', 'SUMMARY'=>'Pełna nazwa przedmiotu']);
$result = Calendar::merge([['id'=>'123','initials'=>'','ics'=>$minimal->serialize()]],['Pełna nazwa przedmiotu'=>'PNP'],'plain');
$parsed = Sabre\VObject\Reader::read($result);
check((string)$parsed->VEVENT->DESCRIPTION==='Przedmiot: Pełna nazwa przedmiotu');
check((string)$parsed->VEVENT->SUMMARY==='PNP');
foreach (['CWP'=>'proj.', 'W'=>'wyk.', 'XYZ'=>'XYZ'] as $prefix=>$label) {
    $result = Calendar::merge([['id'=>'123','initials'=>'','ics'=>str_replace('CWL -', $prefix.' -', $ics)]],[],false);
    $cal = Sabre\VObject\Reader::read($result);
    check((string)$cal->VEVENT->SUMMARY==='TiP '.$label.($prefix === 'W' ? '' : ' (gr. 4)'));
}
foreach (['plain'=>'[AB]', 'unicode'=>'🅰🅱', 'bold-script'=>'𝓐𝓑', 'bold'=>'𝐀𝐁', 'italic'=>'𝐴𝐵', 'sans-bold'=>'𝗔𝗕', 'sans-bold-italic'=>'𝘼𝘽', 'monospace'=>'𝙰𝙱'] as $style=>$expected) {
    check(Calendar::initials('AB', $style)===$expected);
    $result = Calendar::merge([['id'=>'123','initials'=>'AB','ics'=>$ics]],[],$style);
    $cal = Sabre\VObject\Reader::read($result);
    check((string)$cal->VEVENT->SUMMARY===$expected.' TiP lab (gr. 4)');
    check(mb_strlen(Calendar::initials('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $style))===($style==='plain' ? 28 : 26));
}
try {
    Calendar::initials('AB', 'unknown');
    throw new RuntimeException('Invalid style accepted');
} catch (InvalidArgumentException $e) {}
echo "OK\n";
