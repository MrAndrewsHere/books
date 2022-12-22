<?php

namespace Books\Catalog\Updates;

use Books\Catalog\Models\Genre;
use Illuminate\Support\Facades\DB;
use October\Rain\Database\Updates\Seeder;

class SeedAllTables extends Seeder
{
    private array $types = [
        'Электронные книги',
        'Аудио',
        'Бумажные',
        'Комиксы'
    ];

    private array $genres = [
        'Фэнтези' => [
            'Магическое фэнтези',
            'Историческое фэнтези',
            'Тёмное фэнтези',
            'Фольклорное фэнтези',
            'Городское фэнтези',
            'Боевое фэнтези',
            'Эпическое фэнтези',
            'Любовное фэнтези',
            'Приключенческое фэнтези',
            'Юмористическое фэнтези',
            'Бытовое фэнтези',
            'Героическое фэнтези',
        ],
        'Магическая академия' => [],
        'Попаданцы' => [
            'Попаданцы фэнтези',
            'Попаданцы фантастика',
            'Попаданцы во времени',
            'Попаданцы в существ',
            'Попаданцы в книгу',
        ],
        'Космос' => [
            'Космическое фэнтези',
            'Космическая фантастика',
            'Космоопера',
        ],
        'Проза' => [
            'Молодёжная проза',
            'Подростковая проза',
        ],
        'Мистика' => [],
        'Бояръ-аниме' => [],
        'Хобби и досуг' => [
            'Рукоделие и ремёсла',
            'Искусство',
            'Кулинария',
            'Отдых, туризм, путеводители',
            'Природа и животные',
            'Фотография',
            'Автомобили и ПДД',
            'Сад, огород',
            'Рыбалка, охота',
        ],
        'Неформат' => [],
        'Поэзия' => [],
        'Фантастика' => [
            'Любовная фантастика',
            'Приключенческая фантастика',
            'Социальная фантастика',
            'Историческая фантастика',
            'Юмористическая фантастика',
            'Героическая фантастика',
            'Городская фантастика',
            'Боевая фантастика',
            'Постапокалипсис',
            'Научная фантастика',
            'Стимпанк',
            'Альтернативная история',
            'Антиутопия',
            'Киберпанк',
        ],
        '18+' => [
            'Эротический любовный роман',
            'Эротическая фантастика',
            'Эротическое фэнтези',
            'Эротический фанфик',
            'Гарем женский',
            'Гарем мужской',
            'Жесктая эротика',
            'Слеш',
            'Омегаверс',
            'Фемслеш',
            'БДСМ FemDom',
            'БДСМ MaleDom',
        ],
        'Знания' => [
            'Саморазвитие и личностный рост',
            'Научно-популярная литература',
            'Словари, справочники',
            'Эзотерика',
            'Религия',
            'Компьютерная литература',
            'Культура и искусство',
            'Изучение языков',
            'Воспитание детей',
        ],
        'Фанфик' => [
            'Фанфик по книгам',
            'Фанфик по фильмам',
            'Фанфик по комиксам',
            'Фанфик по компьютерным',
        ],
        'Детское' => [
            'Сказки для детей',
            'Детский детектив',
            'Детская фантастика',
            'Детское фэнтези',
            'Стихи для детей',
            'История приключений для детей',
            'Развивающая литература',
        ],
        'Ужасы' => [],
        'Сказка' => [],
        'Уся' => [],
        'Романы' => [
            'Современный любовный роман',
            'Остросюжетный любовный роман',
            'Короткий любовный роман',
            'Исторический любовный роман',
            'Приключенческий любовный роман',
            'Юмористический любовный роман',
            'Психологический любовный роман',
            'Исторический любовный роман',
            'Мужской любовный роман',
        ],
        'Бизнес' => [
            'Экономика и финансы',
            'Инвестиции, ценные бумаги',
            'Менеджмент',
            'Маркетинг и реклама',
            'Создание бизнеса',
            'Бухучёт, налогообложение, аудит',
            'Недвижимость',
            'Логистика',
            'Банки',
            'Управление в бизнес-сфере',
        ],
        'Детективы' => [
            'Классический детектив',
            'Иронический детектив',
            'Исторический детектив',
            'Психологический детектив',
            'Фантастический детектив',
            'Шпионский детектив',
            'Полицейский детектив',
            'Магический детектив',
        ],
        'РПГ' => [
            'ЛитРПГ',
            'РеалРПГ'
        ],
        'Триллер' => [],
        'Янг-эдалт' => [],
        'Драма' => [],
        'Магический реализм' => [],
        'Психология' => [],
        'Красота' => [],
        'Спорт' => [],
    ];

    private array $favorites = [
        'Любовное фэнтези',
        'Приключенческое фэнтези',
        'Магическая академия',
        'Попаданцы',
        'Космос',
        'Бояръ-аниме',
        'Боевая фантастика',
        'Уся',
        'Современный любовный роман',
        'Остросюжетный любовный роман',
        'Короткий любовный роман',
        'Исторический детектив',
        'Магический детектив',
        'РПГ',
        'Янг-эдалт',
    ];

    public function run()
    {
        $data = $this->mapWithName($this->types);
        DB::table('books_catalog_types')->insert($data);

        foreach ($this->genres as $genre => $list) {

            $root = Genre::query()->create(['name' => $genre]);
            foreach ($this->mapWithName($list) as $genreItem) {
                $root->children()->create($genreItem);
            }
        }
        Genre::query()->whereIn('name', $this->favorites)->update(['favorite' => 1]);
    }

    private function mapWithName(array $array): array
    {
        return array_map(fn($item) => ['name' => $item], $array);
    }
}