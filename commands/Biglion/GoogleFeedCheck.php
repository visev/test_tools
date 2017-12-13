<?php

namespace Commands\Biglion;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class GoogleFeedCheck extends Command
{
    protected $urlsMap = [
        [
            'feed' => 'http://api.biglion.ru/api.php?method=get_google_feed&type=csv&site=7&category=131&city=1',
            'site' => 'https://www.biglion.ru/services/',
        ]
    ];

    protected function configure()
    {
        $this
            ->setName('feed:check')
            ->setDescription('Проверка фида get_google_feed')
            ->setHelp('Получает фид get_google_feed, парсит сайт Биглиона и сравнивает результаты')
            ->addArgument('urlsMap', InputArgument::REQUIRED, 'Файл с настройками')
        ;
    }

    /**
     * Возвращает количество страниц
     * @param Crawler $crawler
     * @return int
     */
    protected function getPagesCount(Crawler $crawler): int
    {
        $pages = $crawler->filter('div.page_pavigation .pn_right a');
        if ($pages->count() > 0) {
            return (int)$pages->last()->html();
        }
        return 1;
    }

    protected function getFeedIds(string $feedUrl): array
    {
        $feedContent = file_get_contents($feedUrl);
        $feedRows = array_slice(explode("\n", $feedContent), 1);
        $feedIds = [];
        foreach ($feedRows as $row) {
            $fields = explode(',', $row);
            $dealOfferId = $fields[0];
            if (empty($dealOfferId)) {
                continue;
            }
            $feedIds[] = (int)$dealOfferId;
        }
        return $feedIds;
    }

    protected function getSiteIds(string $siteUrl): array
    {
        $pageContent = file_get_contents($siteUrl);
        $crawler = new Crawler($pageContent);

        $pagesCount = $this->getPagesCount($crawler);

        $siteIds = [];
        $items = $crawler->filter('div.ec_track_item');
        if ($items->count() < 1) {
            return [];
        }
        $items->each(function (Crawler $item) use (&$siteIds) {
            $dealOfferId = (int)$item->attr('data-do_id');
            if ($dealOfferId < 1) {
                return;
            }
            $siteIds[] = $dealOfferId;
        });

        if ($pagesCount > 1) {
            for ($page = 2; $page <= $pagesCount; $page++) {
                $url = $siteUrl . '?page=' . $page;
                $pageContent = file_get_contents($url);
                $crawler = new Crawler($pageContent);
                $items = $crawler->filter('div.ec_track_item');
                $items->each(function (Crawler $item) use (&$siteIds) {
                    $dealOfferId = (int)$item->attr('data-do_id');
                    if ($dealOfferId < 1) {
                        return;
                    }
                    $siteIds[] = $dealOfferId;
                });
            }
        }
        $siteIds = array_unique($siteIds);
        return $siteIds;
    }

    /**
     * Процесс сравнения
     * @param $feedUrl
     * @param $siteUrl
     */
    protected function comparingCount(string $feedUrl, string $siteUrl)
    {
        $feedIds = $this->getFeedIds($feedUrl);
        $siteIds = $this->getSiteIds($siteUrl);

        echo 'Feed url: ', $feedUrl, PHP_EOL;
        echo 'Site url: ', $siteUrl, PHP_EOL;
        echo 'Feed items: ', count($feedIds), PHP_EOL;
        echo 'Site items: ', count($siteIds), PHP_EOL;

        $diff = array_diff($feedIds, $siteIds);
        echo 'Difference by ids: ', count($diff), PHP_EOL;
        if (!empty($diff)) {
            print_r($diff);
        }

        echo '---------------------------------', PHP_EOL;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urlsMapFile = $input->getArgument('urlsMap');

        if (!file_exists($urlsMapFile)) {
            $output->writeln('Файл не найден: ' . $urlsMapFile);
            return;
        }

        $sets = \Symfony\Component\Yaml\Yaml::parseFile($urlsMapFile);

        if (!isset($sets['urls']) || empty($sets['urls'])) {
            $output->writeln('Список пуст');
            return;
        }

        $output->writeln('Файл: ' . $urlsMapFile);
        $output->writeln('Обработка...');

        foreach ($sets['urls'] as $urlMap) {
            $this->comparingCount($urlMap['feed'], $urlMap['site']);
        }
    }
}