# USOS-WebDAV-Proxy

Proxy subskrypcji iCalendar USOS przez HTTPS. PHP 8.2+, rozszerzenia curl i mbstring oraz Composer.

Uruchom `composer install --no-dev` lokalnie lub na serwerze. Wgraj kod wraz z `vendor`. Edytuj `config.php`: wyłącznie szablon URL i mapa nazw przedmiotów na skróty. Serwer musi mieć zapisywalny katalog tymczasowy poza katalogiem publicznym. Cache zawiera prywatne kalendarze; katalog ma uprawnienia 0700.

Przykłady zawierają fikcyjne dane:

- `https://example.org/calendar.php?user_id=12345&key=example-key`
- `https://example.org/calendar.php?source%5B%5D=AB%3A12345%3Aexample-key&source%5B%5D=CD%3A67890%3Aanother-example`

Dodaj `&initials=unicode`, aby wybrać inicjały 🅰🅱. URL można subskrybować w aplikacji kalendarza (także ze schematem webcal, jeśli klient go wymaga). URL zawiera dane dostępowe: nie publikuj go i skonfiguruj logi serwera tak, aby nie zapisywały query string.

Style inicjałów dla agregacji (dopisz jeden argument do URL):

| Argument | Przykład |
| --- | --- |
| `&initials=plain` (domyślny) | [AB] |
| `&initials=unicode` | 🅰🅱 |
| `&initials=bold-script` | 𝓐𝓑 |
| `&initials=bold` | 𝐀𝐁 |
| `&initials=italic` | 𝐴𝐵 |
| `&initials=sans-bold` | 𝗔𝗕 |
| `&initials=sans-bold-italic` | 𝘼𝘽 |
| `&initials=monospace` | 𝙰𝙱 |

Nieznany styl zwraca HTTP 400. Wygląd znaków zależy od czcionek aplikacji kalendarza.

Cache odświeża się przy żądaniu po godzinie. Podczas awarii kopia jest dopuszczalna do wieku 24 godzin. Brak któregokolwiek źródła daje HTTP 502 zamiast częściowego kalendarza. Klient kalendarza sam ustala częstotliwość pobierania. Bez crona.

Domyślne skróty biorą pierwsze litery wyrazów, spójniki i przyimki małą literą. Mapa własnych skrótów ma pierwszeństwo. Rozpoznane rodzaje zajęć: CWL → lab, CWP → proj., W → wyk. Nieznane przedrostki pozostają bez zmian. Lokalizacja pochodzi z kolejnych linii `Sala: numer` i `budynek`, np. C-2. Pozostałe notatki zostają zachowane.

Testy: `composer test`. Katalog `tests` nie musi być publikowany na serwerze.

Projekt jest udostępniany na licencji [MIT](LICENSE). Zależności zachowują własne licencje.

Kod i dokumentacja zostały stworzone przez agenta AI OpenAI Codex na podstawie wymagań i wskazówek autora projektu.
